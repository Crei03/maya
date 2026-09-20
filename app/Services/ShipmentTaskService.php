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
     * Retorna estadísticas KPI agregadas para los planes de entrega.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStats(array $filters = []): array
    {
        $baseQuery = ShipmentTask::query();

        if (! empty($filters['origin_warehouse_id'] ?? null)) {
            $baseQuery->where('origin_warehouse_id', $filters['origin_warehouse_id']);
        }

        if (! empty($filters['date_from'] ?? null)) {
            $baseQuery->whereDate('scheduled_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'] ?? null)) {
            $baseQuery->whereDate('scheduled_date', '<=', $filters['date_to']);
        }

        $total = (clone $baseQuery)->count();

        $cs = app(\App\Services\CatalogoService::class);
        $statuses = $cs->getValoresBySlug('estado-tarea');

        $countsByCode = [
            'PENDIENTE' => 0,
            'EN_PROCESO' => 0,
            'COMPLETADA' => 0,
            'CANCELADA' => 0,
        ];

        foreach ($statuses as $status) {
            $countsByCode[$status->codigo] = 0;
        }

        $byStatusId = (clone $baseQuery)
            ->whereNotNull('status_id')
            ->select('status_id', DB::raw('count(*) as aggregate'))
            ->groupBy('status_id')
            ->pluck('aggregate', 'status_id');

        foreach ($byStatusId as $statusId => $count) {
            $matched = $statuses->firstWhere('id', (int) $statusId);
            if ($matched) {
                $countsByCode[$matched->codigo] = ($countsByCode[$matched->codigo] ?? 0) + (int) $count;
            }
        }

        $pending = $countsByCode['PENDIENTE'] ?? 0;
        $inProgress = $countsByCode['EN_PROCESO'] ?? 0;
        $completed = $countsByCode['COMPLETADA'] ?? 0;
        $cancelled = $countsByCode['CANCELADA'] ?? 0;

        return [
            'total' => $total,
            'by_status' => $countsByCode,
            'summary' => [
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'cancelled' => $cancelled,
            ],
        ];
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
            $statusVal = (string) $filters['status'];
            if (is_numeric($statusVal)) {
                $query->where('status_id', (int) $statusVal);
            } else {
                $query->whereHas('status', function (Builder $q) use ($statusVal): void {
                    $q->where('codigo', strtoupper($statusVal));
                });
            }
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
            'status',
            'driver:id,name,email',
            'vehicle:id,license_plate,brand,model,capacity_kg',
            'warehouse:id,name',
            'items.status',
            'items.priority',
            'items.shipment.status',
            'items.shipment:id,tracking_number,weight_lb,weight_kg,status_id',
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
            'status',
            'driver.driverProfile',
            'vehicle',
            'warehouse',
            'items' => fn ($q) => $q->orderBy('stop_order')->orderBy('created_at'),
            'items.status',
            'items.priority',
            'items.shipment.status',
            'items.shipment.referenceType',
            'items.shipment.packageType',
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
            $cs = app(\App\Services\CatalogoService::class);
            $code = $data['title'] ?? ShipmentTask::generateTaskCode($tenantId);

            $task = new ShipmentTask;
            $task->tenant_id = $tenantId;
            $task->title = $code;
            $task->driver_id = (int) $data['driver_id'];
            $task->vehicle_id = $data['vehicle_id'] ?? null;
            $task->origin_warehouse_id = $data['origin_warehouse_id'];
            $task->scheduled_date = $data['start_date'];
            $task->start_date = $data['start_date'];
            $task->status_id = $cs->getValorIdByCodigo('estado-tarea', 'PENDIENTE');
            $task->notes = $data['notes'] ?? null;
            $task->save();

            // Ordenar items por stop_order asegurado
            $items = $data['items'] ?? [];
            usort($items, fn ($a, $b) => ($a['stop_order'] ?? 0) <=> ($b['stop_order'] ?? 0));

            $assignedStatusId = $cs->getValorIdByCodigo('estado-envio', 'ASIGNADO');
            $itemPendingStatusId = $cs->getValorIdByCodigo('estado-item-tarea', 'PENDIENTE');

            foreach ($items as $index => $itemData) {
                $priority = $itemData['priority'] ?? 'media';
                $priorityId = ! empty($itemData['priority_id'])
                    ? (int) $itemData['priority_id']
                    : ($cs->getValorIdByCodigo('prioridad-tarea', strtoupper((string) $priority))
                        ?? $cs->getValorIdByCodigo('prioridad-tarea', 'MEDIA'));
                $stopOrder = (int) ($itemData['stop_order'] ?? ($index + 1));

                $shipmentId = null;

                if (! empty($itemData['new_shipment'] ?? null)) {
                    $newShipment = $itemData['new_shipment'];
                    $newShipment['tenant_id'] = $tenantId;
                    $newShipment['warehouse_id'] = $task->origin_warehouse_id;
                    $newShipment['driver_task'] = $task->id;
                    $newShipment['status_id'] = $assignedStatusId;

                    if (empty($newShipment['tracking_number'])) {
                        $newShipment['tracking_number'] = Shipment::generateTrackingNumber();
                    }

                    if (! empty($newShipment['sender_id']) && empty($newShipment['recipient_name'])) {
                        $client = \App\Models\Client::find($newShipment['sender_id']);
                        if ($client) {
                            $newShipment['recipient_name'] = $client->full_name ?? trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
                            if (empty($newShipment['recipient_phone'])) {
                                $newShipment['recipient_phone'] = $client->phone;
                            }
                        }
                    }

                    $shipment = app(\App\Services\ShipmentService::class)->create($newShipment);
                    $shipmentId = $shipment->id;
                } elseif (! empty($itemData['shipment_id'] ?? null)) {
                    $shipment = Shipment::findOrFail($itemData['shipment_id']);
                    $shipment->update([
                        'driver_task' => $task->id,
                        'status_id' => $assignedStatusId,
                    ]);
                    $shipmentId = $shipment->id;
                }

                if ($shipmentId) {
                    $item = new ShipmentTaskItem;
                    $item->tenant_id = $tenantId;
                    $item->shipment_task_id = $task->id;
                    $item->shipment_id = $shipmentId;
                    $item->priority_id = $priorityId;
                    $item->stop_order = $stopOrder;
                    $item->status_id = $itemPendingStatusId;
                    $item->save();

                    // Registrar evento de tracking
                    TrackingEvent::create([
                        'tenant_id' => $tenantId,
                        'shipment_id' => $shipmentId,
                        'status_id' => $assignedStatusId,
                        'location_name' => $task->warehouse?->name ?? 'Bodega de salida',
                        'description' => "Asignado al plan de entrega {$task->title}",
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

            $cs = app(\App\Services\CatalogoService::class);

            foreach ($items as $itemData) {
                $updateData = ['stop_order' => $itemData['stop_order']];

                if (! empty($itemData['priority_id'])) {
                    $updateData['priority_id'] = (int) $itemData['priority_id'];
                } elseif (! empty($itemData['priority'])) {
                    $priorityId = $cs->getValorIdByCodigo('prioridad-tarea', strtoupper((string) $itemData['priority']));
                    if ($priorityId) {
                        $updateData['priority_id'] = $priorityId;
                    }
                }

                ShipmentTaskItem::where('id', $itemData['id'])
                    ->where('shipment_task_id', $taskId)
                    ->update($updateData);
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
        $task = ShipmentTask::with('status')->findOrFail($taskId);

        if ($task->status?->codigo !== 'PENDIENTE') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden iniciar tareas en estado pendiente.',
            ]);
        }

        $cs = app(\App\Services\CatalogoService::class);
        $inProgressStatusId = $cs->getValorIdByCodigo('estado-tarea', 'EN_PROCESO');
        $inTransitShipmentStatusId = $cs->getValorIdByCodigo('estado-envio', 'EN_TRANSITO');

        // Regla: un conductor no puede tener 2 tareas in_progress simultáneas
        $hasActiveTask = ShipmentTask::where('driver_id', $task->driver_id)
            ->where('status_id', $inProgressStatusId)
            ->where('id', '!=', $taskId)
            ->exists();

        if ($hasActiveTask) {
            throw ValidationException::withMessages([
                'driver_id' => 'El conductor ya tiene una tarea en progreso. Debe completarla o cancelarla antes de iniciar una nueva.',
            ]);
        }

        return DB::transaction(function () use ($task, $inProgressStatusId, $inTransitShipmentStatusId): ShipmentTask {
            $now = now();
            $task->status_id = $inProgressStatusId;
            $task->started_at = $now;
            $task->start_date = $now;
            $task->save();

            $task->load(['status', 'items.shipment', 'driver', 'warehouse']);

            foreach ($task->items as $item) {
                if ($item->shipment) {
                    $item->shipment->update([
                        'status_id' => $inTransitShipmentStatusId,
                    ]);

                    TrackingEvent::create([
                        'tenant_id' => $task->tenant_id,
                        'shipment_id' => $item->shipment->id,
                        'status_id' => $inTransitShipmentStatusId,
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
        $task = ShipmentTask::with('status')->findOrFail($taskId);

        if ($task->status?->codigo !== 'EN_PROCESO') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden finalizar tareas en progreso.',
            ]);
        }

        $cs = app(\App\Services\CatalogoService::class);
        $completedTaskId = $cs->getValorIdByCodigo('estado-tarea', 'COMPLETADA');
        $retornadoItemId = $cs->getValorIdByCodigo('estado-item-tarea', 'RETORNADO');
        $entregadoItemId = $cs->getValorIdByCodigo('estado-item-tarea', 'ENTREGADO');
        $enBodegaShipmentId = $cs->getValorIdByCodigo('estado-envio', 'EN_BODEGA');

        return DB::transaction(function () use ($task, $completedTaskId, $retornadoItemId, $entregadoItemId, $enBodegaShipmentId): ShipmentTask {
            $endDate = now();
            $startDate = $task->started_at ?? $task->start_date ?? $endDate;
            $diffMinutes = $startDate->diffInMinutes($endDate);
            $totalHours = round($diffMinutes / 60, 2);

            $task->status_id = $completedTaskId;
            $task->end_date = $endDate;
            $task->total_hours = $totalHours;
            if (! $task->started_at && $task->start_date) {
                $task->started_at = $task->start_date;
            }
            $task->save();

            $task->load(['status', 'items.shipment', 'warehouse']);

            foreach ($task->items as $item) {
                if ($item->status_id !== $entregadoItemId) {
                    $item->status_id = $retornadoItemId;
                    if (empty($item->return_reason)) {
                        $item->return_reason = 'Ruta finalizada sin completar visita';
                    }
                    $item->save();

                    if ($item->shipment) {
                        $item->shipment->update([
                            'status_id' => $enBodegaShipmentId,
                            'warehouse_id' => $task->origin_warehouse_id,
                        ]);

                        TrackingEvent::create([
                            'tenant_id' => $task->tenant_id,
                            'shipment_id' => $item->shipment->id,
                            'status_id' => $enBodegaShipmentId,
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
        $task = ShipmentTask::with('status')->findOrFail($taskId);

        if ($task->status?->codigo === 'COMPLETADA') {
            throw ValidationException::withMessages([
                'status' => 'No se puede cancelar una tarea que ya ha sido completada.',
            ]);
        }

        $cs = app(\App\Services\CatalogoService::class);
        $cancelledTaskId = $cs->getValorIdByCodigo('estado-tarea', 'CANCELADA');
        $retornadoItemId = $cs->getValorIdByCodigo('estado-item-tarea', 'RETORNADO');
        $entregadoItemId = $cs->getValorIdByCodigo('estado-item-tarea', 'ENTREGADO');
        $enBodegaShipmentId = $cs->getValorIdByCodigo('estado-envio', 'EN_BODEGA');

        return DB::transaction(function () use ($task, $reason, $cancelledTaskId, $retornadoItemId, $entregadoItemId, $enBodegaShipmentId): ShipmentTask {
            $task->status_id = $cancelledTaskId;
            $cancelNote = 'Cancelado: '.($reason ?? 'Cancelado por el gestor');
            $task->notes = $task->notes ? "{$task->notes} | {$cancelNote}" : $cancelNote;
            $task->save();

            $task->load(['status', 'items.shipment', 'warehouse']);

            foreach ($task->items as $item) {
                if ($item->status_id !== $entregadoItemId) {
                    $item->status_id = $retornadoItemId;
                    $item->return_reason = $reason ?? 'Plan de entrega cancelado';
                    $item->save();

                    if ($item->shipment) {
                        $item->shipment->update([
                            'status_id' => $enBodegaShipmentId,
                            'driver_task' => null,
                            'warehouse_id' => $task->origin_warehouse_id,
                        ]);

                        TrackingEvent::create([
                            'tenant_id' => $task->tenant_id,
                            'shipment_id' => $item->shipment->id,
                            'status_id' => $enBodegaShipmentId,
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
    public function updateItemStatus(string $taskId, string $itemId, string|int $status, ?string $returnReason = null): ShipmentTaskItem
    {
        $task = ShipmentTask::with('status')->findOrFail($taskId);

        $item = ShipmentTaskItem::where('id', $itemId)
            ->where('shipment_task_id', $taskId)
            ->firstOrFail();

        $cs = app(\App\Services\CatalogoService::class);
        $statusId = is_numeric($status) ? (int) $status : $cs->getValorIdByCodigo('estado-item-tarea', strtoupper((string) $status));
        if (! $statusId) {
            $codeMap = [
                'pendiente' => 'PENDIENTE',
                'entregado' => 'ENTREGADO',
                'retornado' => 'RETORNADO',
                'in_progress' => 'PENDIENTE',
            ];
            $code = $codeMap[strtolower((string) $status)] ?? strtoupper((string) $status);
            $statusId = $cs->getValorIdByCodigo('estado-item-tarea', $code);
        }

        $entregadoStatusId = $cs->getValorIdByCodigo('estado-item-tarea', 'ENTREGADO');
        $retornadoStatusId = $cs->getValorIdByCodigo('estado-item-tarea', 'RETORNADO');
        $pendienteStatusId = $cs->getValorIdByCodigo('estado-item-tarea', 'PENDIENTE');

        $deliveredShipmentId = $cs->getValorIdByCodigo('estado-envio', 'ENTREGADO');
        $returnedShipmentId = $cs->getValorIdByCodigo('estado-envio', 'DEVUELTO');
        $inTransitShipmentId = $cs->getValorIdByCodigo('estado-envio', 'EN_TRANSITO');
        $assignedShipmentId = $cs->getValorIdByCodigo('estado-envio', 'ASIGNADO');

        return DB::transaction(function () use ($task, $item, $statusId, $entregadoStatusId, $retornadoStatusId, $pendienteStatusId, $deliveredShipmentId, $returnedShipmentId, $inTransitShipmentId, $assignedShipmentId, $returnReason): ShipmentTaskItem {
            if ($statusId === $entregadoStatusId) {
                $item->status_id = $entregadoStatusId;
                $item->delivered_at = now();
                $item->return_reason = null;
                $item->save();

                if ($item->shipment) {
                    $item->shipment->update([
                        'status_id' => $deliveredShipmentId,
                        'delivered_at' => now(),
                    ]);

                    TrackingEvent::create([
                        'tenant_id' => $task->tenant_id,
                        'shipment_id' => $item->shipment->id,
                        'status_id' => $deliveredShipmentId,
                        'location_name' => 'Destino de entrega',
                        'description' => "Entregado exitosamente en parada #{$item->stop_order}",
                        'created_by' => auth()->id() ? (string) auth()->id() : null,
                        'timestamp' => now(),
                    ]);
                }
            } elseif ($statusId === $retornadoStatusId) {
                $item->status_id = $retornadoStatusId;
                $item->delivered_at = null;
                $item->return_reason = $returnReason ?? 'No entregado';
                $item->save();

                if ($item->shipment) {
                    $item->shipment->update([
                        'status_id' => $returnedShipmentId,
                    ]);

                    TrackingEvent::create([
                        'tenant_id' => $task->tenant_id,
                        'shipment_id' => $item->shipment->id,
                        'status_id' => $returnedShipmentId,
                        'location_name' => 'En ruta',
                        'description' => "Intento de entrega no exitoso en parada #{$item->stop_order}. Motivo: {$item->return_reason}",
                        'created_by' => auth()->id() ? (string) auth()->id() : null,
                        'timestamp' => now(),
                    ]);
                }
            } elseif ($statusId === $pendienteStatusId) {
                $item->status_id = $pendienteStatusId;
                $item->delivered_at = null;
                $item->return_reason = null;
                $item->save();

                if ($item->shipment) {
                    $newShipmentStatusId = $task->status?->codigo === 'EN_PROCESO' ? $inTransitShipmentId : $assignedShipmentId;
                    $item->shipment->update(['status_id' => $newShipmentStatusId]);
                }
            }

            return $item->fresh(['shipment.sender', 'status', 'priority']);
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
    public function assignShipments(string $taskId, array $shipmentIds, string|int $priority = 'media'): ShipmentTask
    {
        $task = ShipmentTask::with('status')->findOrFail($taskId);

        if ($task->status?->codigo !== 'PENDIENTE') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden asignar paquetes a planes en estado pendiente.',
            ]);
        }

        $cs = app(\App\Services\CatalogoService::class);
        $assignedStatusId = $cs->getValorIdByCodigo('estado-envio', 'ASIGNADO');
        $itemPendingStatusId = $cs->getValorIdByCodigo('estado-item-tarea', 'PENDIENTE');

        $priorityId = is_numeric($priority)
            ? (int) $priority
            : ($cs->getValorIdByCodigo('prioridad-tarea', strtoupper((string) $priority))
                ?? $cs->getValorIdByCodigo('prioridad-tarea', 'MEDIA'));

        return DB::transaction(function () use ($task, $shipmentIds, $priorityId, $assignedStatusId, $itemPendingStatusId): ShipmentTask {
            $maxOrder = (int) $task->items()->max('stop_order') ?: 0;

            foreach ($shipmentIds as $shipmentId) {
                if ($task->items()->where('shipment_id', $shipmentId)->exists()) {
                    continue;
                }

                $shipment = Shipment::findOrFail($shipmentId);
                $shipment->update([
                    'driver_task' => $task->id,
                    'status_id' => $assignedStatusId,
                ]);

                $maxOrder++;
                $item = new ShipmentTaskItem;
                $item->tenant_id = $task->tenant_id;
                $item->shipment_task_id = $task->id;
                $item->shipment_id = $shipmentId;
                $item->priority_id = $priorityId;
                $item->stop_order = $maxOrder;
                $item->status_id = $itemPendingStatusId;
                $item->save();

                TrackingEvent::create([
                    'tenant_id' => $task->tenant_id,
                    'shipment_id' => $shipmentId,
                    'status_id' => $assignedStatusId,
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
        $task = ShipmentTask::with('status')->findOrFail($taskId);

        if ($task->status?->codigo !== 'PENDIENTE') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden desasignar paquetes de planes en estado pendiente.',
            ]);
        }

        $cs = app(\App\Services\CatalogoService::class);
        $inWarehouseStatusId = $cs->getValorIdByCodigo('estado-envio', 'EN_BODEGA');

        return DB::transaction(function () use ($task, $shipmentId, $inWarehouseStatusId): ShipmentTask {
            $item = $task->items()->where('shipment_id', $shipmentId)->first();
            if ($item) {
                $item->delete();
            }

            $shipment = Shipment::find($shipmentId);
            if ($shipment) {
                $shipment->update([
                    'driver_task' => null,
                    'status_id' => $inWarehouseStatusId,
                ]);

                TrackingEvent::create([
                    'tenant_id' => $task->tenant_id,
                    'shipment_id' => $shipmentId,
                    'status_id' => $inWarehouseStatusId,
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
            $priorityKey = strtolower($item->priority?->codigo ?? 'media');
            if (isset($priorityCounts[$priorityKey])) {
                $priorityCounts[$priorityKey]++;
            } else {
                $priorityCounts[$priorityKey] = 1;
            }

            $itemCode = $item->status?->codigo;
            if ($itemCode === 'ENTREGADO') {
                $deliveredCount++;
            } elseif ($itemCode === 'RETORNADO') {
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

        $statusCode = $task->status?->codigo ?? 'PENDIENTE';
        $statusLabel = $task->status?->valor ?? 'Pendiente';
        $statusMetadata = $task->status?->metadata ?? [];

        $scheduledDate = $task->scheduled_date ?? $task->start_date;
        $startedAt = $task->started_at;
        if (! $startedAt && in_array($statusCode, ['EN_PROCESO', 'COMPLETADA'], true)) {
            $startedAt = $task->start_date;
        }

        $durationFormatted = null;
        if ($task->total_hours !== null) {
            $totalMinutes = (int) round(((float) $task->total_hours) * 60);
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            $durationFormatted = "{$hours}h ".str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).'m';
        } elseif ($startedAt && $task->end_date) {
            $diffMinutes = $startedAt->diffInMinutes($task->end_date);
            $hours = intdiv((int) $diffMinutes, 60);
            $minutes = ((int) $diffMinutes) % 60;
            $durationFormatted = "{$hours}h ".str_pad((string) $minutes, 2, '0', STR_PAD_LEFT).'m';
        }

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
            'scheduled_date' => $scheduledDate ? $scheduledDate->format('Y-m-d H:i') : null,
            'scheduled_date_raw' => $scheduledDate ? $scheduledDate->toIso8601String() : null,
            'started_at' => $startedAt ? $startedAt->format('Y-m-d H:i') : null,
            'started_at_raw' => $startedAt ? $startedAt->toIso8601String() : null,
            'start_date' => $task->start_date ? $task->start_date->format('Y-m-d H:i') : null,
            'start_date_raw' => $task->start_date ? $task->start_date->toIso8601String() : null,
            'end_date' => $task->end_date ? $task->end_date->format('Y-m-d H:i') : null,
            'end_date_raw' => $task->end_date ? $task->end_date->toIso8601String() : null,
            'total_hours' => $task->total_hours !== null ? (float) $task->total_hours : null,
            'duration_formatted' => $durationFormatted,
            'status_id' => $task->status_id,
            'status' => $statusCode,
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'status_metadata' => $statusMetadata,
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
                    'priority_id' => $item->priority_id,
                    'priority' => $item->priority?->codigo ? strtolower($item->priority->codigo) : 'media',
                    'priority_code' => $item->priority?->codigo ?? 'MEDIA',
                    'priority_label' => $item->priority?->valor ?? 'Media',
                    'priority_metadata' => $item->priority?->metadata ?? [],
                    'status_id' => $item->status_id,
                    'status' => $item->status?->codigo ?? 'PENDIENTE',
                    'status_code' => $item->status?->codigo ?? 'PENDIENTE',
                    'status_label' => $item->status?->valor ?? 'Pendiente',
                    'status_metadata' => $item->status?->metadata ?? [],
                    'delivered_at' => $item->delivered_at ? $item->delivered_at->format('Y-m-d H:i') : null,
                    'return_reason' => $item->return_reason,
                    'shipment' => $shipment ? [
                        'id' => $shipment->id,
                        'tracking_number' => $shipment->tracking_number,
                        'reference_type_id' => $shipment->reference_type_id,
                        'reference_type' => $shipment->referenceType?->valor ?? $shipment->referenceType?->codigo,
                        'reference_type_code' => $shipment->referenceType?->codigo,
                        'reference_number' => $shipment->reference_number,
                        'lpn_code' => $shipment->lpn_code,
                        'pieces_count' => $shipment->pieces_count ?? 1,
                        'package_type_id' => $shipment->package_type_id,
                        'package_type' => $shipment->packageType?->valor ?? $shipment->packageType?->codigo,
                        'package_type_code' => $shipment->packageType?->codigo,
                        'weight_lb' => $shipment->weight_lb,
                        'weight_kg' => $shipment->weight_kg,
                        'destination_address' => $shipment->destination_address,
                        'status_id' => $shipment->status_id,
                        'status' => $shipment->status?->codigo ?? 'PENDIENTE',
                        'status_code' => $shipment->status?->codigo ?? 'PENDIENTE',
                        'status_label' => $shipment->status?->valor ?? 'Pendiente',
                        'status_metadata' => $shipment->status?->metadata ?? [],
                        'recipient_name' => $shipment->recipient_name ?: ($shipment->sender ? ($shipment->sender->full_name ?? ($shipment->sender->first_name.' '.$shipment->sender->last_name)) : 'Sin destinatario'),
                        'recipient_phone' => $shipment->recipient_phone ?: ($shipment->sender?->phone ?? ''),
                        'sender_name' => $shipment->recipient_name ?: ($shipment->sender ? ($shipment->sender->full_name ?? ($shipment->sender->first_name.' '.$shipment->sender->last_name)) : 'Sin cliente'),
                        'sender_phone' => $shipment->recipient_phone ?: ($shipment->sender?->phone ?? ''),
                    ] : null,
                ];
            })->all();
        }

        return $data;
    }
}
