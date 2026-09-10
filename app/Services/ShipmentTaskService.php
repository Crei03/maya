<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Models\TrackingEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentTaskService
{
    /**
     * Obtiene el siguiente código secuencial disponible para el tenant.
     */
    public function getNextCode(?string $tenantId = null): string
    {
        return ShipmentTask::generateTaskCode($tenantId);
    }

    /**
     * Listar planes de entrega con paginación y filtros.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $query = ShipmentTask::query();

        if (! empty($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['driver_id'] ?? null)) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (! empty($filters['origin_warehouse_id'] ?? null)) {
            $query->where('origin_warehouse_id', $filters['origin_warehouse_id']);
        }

        if (! empty($filters['date_from'] ?? null)) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'] ?? null)) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'] ?? null)) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('driver', function (Builder $dq) use ($search): void {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->with([
            'driver:id,name,email',
            'vehicle:id,license_plate,brand,model,capacity_kg',
            'warehouse:id,name',
            'items.shipment:id,tracking_number,weight_lb,weight_kg,status',
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'data' => $paginator->through(fn (ShipmentTask $task) => $this->mapTask($task))->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Muestra el detalle completo de un plan de entrega.
     *
     * @return array<string, mixed>
     *
     * @throws ModelNotFoundException
     */
    public function show(string $id): array
    {
        $task = ShipmentTask::query()->find($id);

        if ($task === null) {
            throw (new ModelNotFoundException)->setModel(ShipmentTask::class, $id);
        }

        $task->load([
            'driver.driverProfile',
            'vehicle',
            'warehouse',
            'items' => fn ($q) => $q->orderBy('stop_order')->orderBy('created_at'),
            'items.shipment.sender',
            'items.shipment.warehouse',
        ]);

        return $this->mapTask($task, true);
    }

    /**
     * Crea un plan de entrega desde el Wizard de manera atómica.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromWizard(array $data, ?string $tenantId = null): ShipmentTask
    {
        return DB::transaction(function () use ($data, $tenantId): ShipmentTask {
            $tenantId = $tenantId ?? auth()->user()?->tenant_id;
            $code = $data['title'] ?? ShipmentTask::generateTaskCode($tenantId);

            $task = new ShipmentTask;
            $task->tenant_id = $tenantId;
            $task->title = $code;
            $task->driver_id = (int) $data['driver_id'];
            $task->vehicle_id = $data['vehicle_id'] ?? null;
            $task->origin_warehouse_id = $data['origin_warehouse_id'];
            $task->start_date = $data['start_date'];
            $task->status = 'pending';
            $task->notes = $data['notes'] ?? null;
            $task->save();

            // Ordenar items por stop_order asegurado
            $items = $data['items'] ?? [];
            usort($items, fn ($a, $b) => ($a['stop_order'] ?? 0) <=> ($b['stop_order'] ?? 0));

            foreach ($items as $index => $itemData) {
                $priority = $itemData['priority'] ?? ShipmentTaskItem::PRIORITY_MEDIA;
                $stopOrder = (int) ($itemData['stop_order'] ?? ($index + 1));

                $shipmentId = null;

                if (! empty($itemData['new_shipment'] ?? null)) {
                    $newShipment = $itemData['new_shipment'];
                    $newShipment['tenant_id'] = $tenantId;
                    $newShipment['warehouse_id'] = $task->origin_warehouse_id;
                    $newShipment['driver_task'] = $task->id;
                    $newShipment['status'] = Shipment::STATUS_ASSIGNED;

                    if (empty($newShipment['tracking_number'])) {
                        $newShipment['tracking_number'] = Shipment::generateTrackingNumber();
                    }

                    $shipment = Shipment::create($newShipment);
                    $shipmentId = $shipment->id;
                } elseif (! empty($itemData['shipment_id'] ?? null)) {
                    $shipment = Shipment::findOrFail($itemData['shipment_id']);
                    $shipment->update([
                        'driver_task' => $task->id,
                        'status' => Shipment::STATUS_ASSIGNED,
                    ]);
                    $shipmentId = $shipment->id;
                }

                if ($shipmentId) {
                    $item = new ShipmentTaskItem;
                    $item->tenant_id = $tenantId;
                    $item->shipment_task_id = $task->id;
                    $item->shipment_id = $shipmentId;
                    $item->priority = $priority;
                    $item->stop_order = $stopOrder;
                    $item->status = ShipmentTaskItem::STATUS_PENDIENTE;
                    $item->save();

                    // Registrar evento de tracking
                    TrackingEvent::create([
                        'tenant_id' => $tenantId,
                        'shipment_id' => $shipmentId,
                        'status_id' => null,
                        'location_name' => $task->warehouse?->name ?? 'Bodega de salida',
                        'description' => "Asignado al plan de entrega {$task->title} con prioridad ".strtoupper($priority),
                        'created_by' => auth()->id() ? (string) auth()->id() : null,
                        'timestamp' => now(),
                    ]);
                }
            }

            $task->load([
                'driver',
                'vehicle',
                'warehouse',
                'items.shipment.sender',
            ]);

            return $task;
        });
    }

    /**
     * Reordena las paradas de un plan respetando las prioridades.
     *
     * @param  array<int, array{id: string, stop_order: int, priority: string}>  $items
     *
     * @throws ModelNotFoundException
     */
    public function reorderItems(string $taskId, array $items): ShipmentTask
    {
        return DB::transaction(function () use ($taskId, $items): ShipmentTask {
            $task = ShipmentTask::findOrFail($taskId);

            foreach ($items as $itemData) {
                ShipmentTaskItem::where('id', $itemData['id'])
                    ->where('shipment_task_id', $taskId)
                    ->update([
                        'stop_order' => $itemData['stop_order'],
                        'priority' => $itemData['priority'],
                    ]);
            }

            return $task->load('items.shipment.sender');
        });
    }

    /**
     * Inicia una tarea de reparto pasando a in_progress y sus envíos a in_transit.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function startTask(string $taskId): ShipmentTask
    {
        $task = ShipmentTask::findOrFail($taskId);

        if ($task->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden iniciar tareas en estado pendiente.',
            ]);
        }

        // Regla: un conductor no puede tener 2 tareas in_progress simultáneas
        $hasActiveTask = ShipmentTask::where('driver_id', $task->driver_id)
            ->where('status', 'in_progress')
            ->where('id', '!=', $taskId)
            ->exists();

        if ($hasActiveTask) {
            throw ValidationException::withMessages([
                'driver_id' => 'El conductor ya tiene una tarea en progreso. Debe completarla o cancelarla antes de iniciar una nueva.',
            ]);
        }

        return DB::transaction(function () use ($task): ShipmentTask {
            $task->status = 'in_progress';
            $task->start_date = now();
            $task->save();

            $task->load(['items.shipment', 'driver', 'warehouse']);

            foreach ($task->items as $item) {
                if ($item->shipment) {
                    $item->shipment->update([
                        'status' => Shipment::STATUS_IN_TRANSIT,
                    ]);

                    TrackingEvent::create([
                        'tenant_id' => $task->tenant_id,
                        'shipment_id' => $item->shipment->id,
                        'status_id' => null,
                        'location_name' => $task->warehouse?->name ?? 'En ruta',
                        'description' => "En ruta de entrega con el conductor {$task->driver?->name} (Plan: {$task->title})",
                        'created_by' => auth()->id() ? (string) auth()->id() : null,
                        'timestamp' => now(),
                    ]);
                }
            }

            return $task;
        });
    }

    /**
     * Finaliza una tarea de reparto, calcula tiempo y retorna paquetes no entregados a bodega.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function completeTask(string $taskId): ShipmentTask
    {
        $task = ShipmentTask::findOrFail($taskId);

        if ($task->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden finalizar tareas en progreso.',
            ]);
        }

        return DB::transaction(function () use ($task): ShipmentTask {
            $endDate = now();
            $startDate = $task->start_date ?? $endDate;
            $diffMinutes = $startDate->diffInMinutes($endDate);
            $totalHours = round($diffMinutes / 60, 2);

            $task->status = 'completed';
            $task->end_date = $endDate;
            $task->total_hours = $totalHours;
            $task->save();

            $task->load(['items.shipment', 'warehouse']);

            foreach ($task->items as $item) {
                if ($item->status !== ShipmentTaskItem::STATUS_ENTREGADO) {
                    $item->status = ShipmentTaskItem::STATUS_RETORNADO;
                    if (empty($item->return_reason)) {
                        $item->return_reason = 'Ruta finalizada sin completar visita';
                    }
                    $item->save();

                    if ($item->shipment) {
                        $item->shipment->update([
                            'status' => Shipment::STATUS_IN_WAREHOUSE,
                            'warehouse_id' => $task->origin_warehouse_id,
                        ]);

                        TrackingEvent::create([
                            'tenant_id' => $task->tenant_id,
                            'shipment_id' => $item->shipment->id,
                            'status_id' => null,
                            'location_name' => $task->warehouse?->name ?? 'Bodega de origen',
                            'description' => "Reingresado a bodega tras finalización de ruta ({$task->title}). Motivo: {$item->return_reason}",
                            'created_by' => auth()->id() ? (string) auth()->id() : null,
                            'timestamp' => now(),
                        ]);
                    }
                }
            }

            return $task;
        });
    }

    /**
     * Cancela una tarea de reparto y retorna sus paquetes no entregados a la bodega.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function cancelTask(string $taskId, ?string $reason = null): ShipmentTask
    {
        $task = ShipmentTask::findOrFail($taskId);

        if ($task->status === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'No se puede cancelar una tarea que ya ha sido completada.',
            ]);
        }

        return DB::transaction(function () use ($task, $reason): ShipmentTask {
            $task->status = 'cancelled';
            $cancelNote = 'Cancelado: '.($reason ?? 'Cancelado por el gestor');
            $task->notes = $task->notes ? "{$task->notes} | {$cancelNote}" : $cancelNote;
            $task->save();

            $task->load(['items.shipment', 'warehouse']);

            foreach ($task->items as $item) {
                if ($item->status !== ShipmentTaskItem::STATUS_ENTREGADO) {
                    $item->status = ShipmentTaskItem::STATUS_RETORNADO;
                    $item->return_reason = $reason ?? 'Plan de entrega cancelado';
                    $item->save();

                    if ($item->shipment) {
                        $item->shipment->update([
                            'status' => Shipment::STATUS_IN_WAREHOUSE,
                            'driver_task' => null,
                            'warehouse_id' => $task->origin_warehouse_id,
                        ]);

                        TrackingEvent::create([
                            'tenant_id' => $task->tenant_id,
                            'shipment_id' => $item->shipment->id,
                            'status_id' => null,
                            'location_name' => $task->warehouse?->name ?? 'Bodega de origen',
                            'description' => "Plan cancelado ({$task->title}). Reingresado a inventario de bodega.",
                            'created_by' => auth()->id() ? (string) auth()->id() : null,
                            'timestamp' => now(),
                        ]);
                    }
                }
            }

            return $task;
        });
    }

    /**
     * Actualiza el estado de una parada específica (entregado o devuelto).
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function updateItemStatus(string $taskId, string $itemId, string $status, ?string $returnReason = null): ShipmentTaskItem
    {
        $task = ShipmentTask::findOrFail($taskId);

        $item = ShipmentTaskItem::where('id', $itemId)
            ->where('shipment_task_id', $taskId)
            ->firstOrFail();

        return DB::transaction(function () use ($task, $item, $status, $returnReason): ShipmentTaskItem {
            if ($status === ShipmentTaskItem::STATUS_ENTREGADO) {
                $item->status = ShipmentTaskItem::STATUS_ENTREGADO;
                $item->delivered_at = now();
                $item->return_reason = null;
                $item->save();

                if ($item->shipment) {
                    $item->shipment->update([
                        'status' => Shipment::STATUS_DELIVERED,
                        'delivered_at' => now(),
                    ]);

                    TrackingEvent::create([
                        'tenant_id' => $task->tenant_id,
                        'shipment_id' => $item->shipment->id,
                        'status_id' => null,
                        'location_name' => 'Destino de entrega',
                        'description' => "Entregado exitosamente en parada #{$item->stop_order}",
                        'created_by' => auth()->id() ? (string) auth()->id() : null,
                        'timestamp' => now(),
                    ]);
                }
            } elseif ($status === ShipmentTaskItem::STATUS_RETORNADO) {
                $item->status = ShipmentTaskItem::STATUS_RETORNADO;
                $item->delivered_at = null;
                $item->return_reason = $returnReason ?? 'No entregado';
                $item->save();

                if ($item->shipment) {
                    $item->shipment->update([
                        'status' => Shipment::STATUS_RETURNED,
                    ]);

                    TrackingEvent::create([
                        'tenant_id' => $task->tenant_id,
                        'shipment_id' => $item->shipment->id,
                        'status_id' => null,
                        'location_name' => 'En ruta',
                        'description' => "Intento de entrega no exitoso en parada #{$item->stop_order}. Motivo: {$item->return_reason}",
                        'created_by' => auth()->id() ? (string) auth()->id() : null,
                        'timestamp' => now(),
                    ]);
                }
            } elseif ($status === ShipmentTaskItem::STATUS_PENDIENTE) {
                $item->status = ShipmentTaskItem::STATUS_PENDIENTE;
                $item->delivered_at = null;
                $item->return_reason = null;
                $item->save();

                if ($item->shipment) {
                    $newShipmentStatus = $task->status === 'in_progress' ? Shipment::STATUS_IN_TRANSIT : Shipment::STATUS_ASSIGNED;
                    $item->shipment->update(['status' => $newShipmentStatus]);
                }
            }

            return $item->fresh(['shipment.sender']);
        });
    }

    /**
     * Asigna envíos existentes de bodega a una tarea pendiente.
     *
     * @param  array<int, string>  $shipmentIds
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function assignShipments(string $taskId, array $shipmentIds, string $priority = 'media'): ShipmentTask
    {
        $task = ShipmentTask::findOrFail($taskId);

        if ($task->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden asignar paquetes a planes en estado pendiente.',
            ]);
        }

        return DB::transaction(function () use ($task, $shipmentIds, $priority): ShipmentTask {
            $maxOrder = (int) $task->items()->max('stop_order') ?: 0;

            foreach ($shipmentIds as $shipmentId) {
                if ($task->items()->where('shipment_id', $shipmentId)->exists()) {
                    continue;
                }

                $shipment = Shipment::findOrFail($shipmentId);
                $shipment->update([
                    'driver_task' => $task->id,
                    'status' => Shipment::STATUS_ASSIGNED,
                ]);

                $maxOrder++;
                $item = new ShipmentTaskItem;
                $item->tenant_id = $task->tenant_id;
                $item->shipment_task_id = $task->id;
                $item->shipment_id = $shipmentId;
                $item->priority = $priority;
                $item->stop_order = $maxOrder;
                $item->status = ShipmentTaskItem::STATUS_PENDIENTE;
                $item->save();

                TrackingEvent::create([
                    'tenant_id' => $task->tenant_id,
                    'shipment_id' => $shipmentId,
                    'status_id' => null,
                    'location_name' => $task->warehouse?->name ?? 'Bodega',
                    'description' => "Asignado al plan de entrega {$task->title}",
                    'created_by' => auth()->id() ? (string) auth()->id() : null,
                    'timestamp' => now(),
                ]);
            }

            return $task->fresh(['driver', 'vehicle', 'warehouse', 'items.shipment.sender']);
        });
    }

    /**
     * Desasigna un paquete de una tarea pendiente.
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function unassignShipment(string $taskId, string $shipmentId): ShipmentTask
    {
        $task = ShipmentTask::findOrFail($taskId);

        if ($task->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden desasignar paquetes de planes en estado pendiente.',
            ]);
        }

        return DB::transaction(function () use ($task, $shipmentId): ShipmentTask {
            $item = $task->items()->where('shipment_id', $shipmentId)->first();
            if ($item) {
                $item->delete();
            }

            $shipment = Shipment::find($shipmentId);
            if ($shipment) {
                $shipment->update([
                    'driver_task' => null,
                    'status' => Shipment::STATUS_IN_WAREHOUSE,
                ]);

                TrackingEvent::create([
                    'tenant_id' => $task->tenant_id,
                    'shipment_id' => $shipmentId,
                    'status_id' => null,
                    'location_name' => $task->warehouse?->name ?? 'Bodega',
                    'description' => "Desasignado del plan {$task->title}. Reingresado a inventario.",
                    'created_by' => auth()->id() ? (string) auth()->id() : null,
                    'timestamp' => now(),
                ]);
            }

            // Reordenar paradas restantes
            $remaining = $task->items()->orderBy('stop_order')->get();
            foreach ($remaining as $idx => $remItem) {
                $remItem->update(['stop_order' => $idx + 1]);
            }

            return $task->fresh(['driver', 'vehicle', 'warehouse', 'items.shipment.sender']);
        });
    }

    /**
     * Mapea el modelo ShipmentTask a un array para la respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function mapTask(ShipmentTask $task, bool $includeDetails = false): array
    {
        $items = $task->items ?? collect();

        $totalWeightLb = 0.0;
        $totalWeightKg = 0.0;
        $priorityCounts = ['alta' => 0, 'media' => 0, 'baja' => 0];
        $deliveredCount = 0;
        $returnedCount = 0;
        $pendingCount = 0;

        foreach ($items as $item) {
            $priorityCounts[$item->priority] = ($priorityCounts[$item->priority] ?? 0) + 1;
            if ($item->status === ShipmentTaskItem::STATUS_ENTREGADO) {
                $deliveredCount++;
            } elseif ($item->status === ShipmentTaskItem::STATUS_RETORNADO) {
                $returnedCount++;
            } else {
                $pendingCount++;
            }

            if ($item->shipment) {
                $totalWeightLb += (float) ($item->shipment->weight_lb ?? 0);
                $totalWeightKg += (float) ($item->shipment->weight_kg ?? 0);
            }
        }

        $totalItems = $items->count();
        $progressPercent = $totalItems > 0 ? (int) round(($deliveredCount / $totalItems) * 100) : 0;

        $data = [
            'id' => $task->id,
            'title' => $task->title,
            'driver_id' => $task->driver_id,
            'driver_name' => $task->driver?->name ?? 'Sin asignar',
            'driver_email' => $task->driver?->email ?? null,
            'vehicle_id' => $task->vehicle_id,
            'vehicle_label' => $task->vehicle ? "{$task->vehicle->brand} {$task->vehicle->model} ({$task->vehicle->license_plate})" : 'Sin vehículo',
            'vehicle_capacity_kg' => $task->vehicle?->capacity_kg ?? null,
            'origin_warehouse_id' => $task->origin_warehouse_id,
            'warehouse_name' => $task->warehouse?->name ?? 'Sin bodega',
            'start_date' => $task->start_date ? $task->start_date->format('Y-m-d H:i') : null,
            'start_date_raw' => $task->start_date ? $task->start_date->toIso8601String() : null,
            'end_date' => $task->end_date ? $task->end_date->format('Y-m-d H:i') : null,
            'total_hours' => $task->total_hours !== null ? (float) $task->total_hours : null,
            'status' => $task->status,
            'notes' => $task->notes,
            'total_items' => $totalItems,
            'packages_count' => $totalItems,
            'delivered_count' => $deliveredCount,
            'returned_count' => $returnedCount,
            'pending_count' => $pendingCount,
            'progress_percent' => $progressPercent,
            'total_weight_lb' => round($totalWeightLb, 2),
            'total_weight_kg' => round($totalWeightKg, 2),
            'priority_counts' => $priorityCounts,
            'created_at' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : null,
        ];

        if ($includeDetails) {
            $data['items'] = $items->map(function (ShipmentTaskItem $item) {
                $shipment = $item->shipment;

                return [
                    'id' => $item->id,
                    'stop_order' => $item->stop_order,
                    'priority' => $item->priority,
                    'status' => $item->status,
                    'delivered_at' => $item->delivered_at ? $item->delivered_at->format('Y-m-d H:i') : null,
                    'return_reason' => $item->return_reason,
                    'shipment' => $shipment ? [
                        'id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                        'package_type' => $shipment->package_type,
                        'weight_lb' => $shipment->weight_lb,
                        'weight_kg' => $shipment->weight_kg,
                        'destination_address' => $shipment->destination_address,
                        'status' => $shipment->status,
                        'sender_name' => $shipment->sender ? ($shipment->sender->full_name ?? ($shipment->sender->first_name.' '.$shipment->sender->last_name)) : 'Sin cliente',
                        'sender_phone' => $shipment->sender?->phone ?? '',
                    ] : null,
                ];
            })->all();
        }

        return $data;
    }
}
