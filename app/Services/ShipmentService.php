<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ShipmentHasRelationsException;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    /**
     * List shipments with pagination and optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = Shipment::query()->orderByDesc('created_at');

        if (! empty($filters['status'] ?? null)) {
            $query->byStatus($filters['status']);
        }

        if (! empty($filters['search'] ?? null)) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('lpn_code', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhereHas('sender', function (Builder $q) use ($search): void {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['reference_type'] ?? null)) {
            if (is_numeric($filters['reference_type'])) {
                $query->where('reference_type_id', (int) $filters['reference_type']);
            } else {
                $query->whereHas('referenceType', function (Builder $q) use ($filters): void {
                    $q->where('codigo', strtoupper((string) $filters['reference_type']));
                });
            }
        }

        if (! empty($filters['lpn_code'] ?? null)) {
            $query->where('lpn_code', 'like', "%{$filters['lpn_code']}%");
        }

        if (! empty($filters['warehouse_id'] ?? null)) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['package_type'] ?? null)) {
            if (is_numeric($filters['package_type'])) {
                $query->where('package_type_id', (int) $filters['package_type']);
            } else {
                $query->whereHas('packageType', function (Builder $q) use ($filters): void {
                    $q->where('codigo', strtoupper((string) $filters['package_type']));
                });
            }
        }

        if (! empty($filters['date_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['driver_id'] ?? null)) {
            $query->whereHas('driverTask', function (Builder $q) use ($filters): void {
                $q->where('driver_id', $filters['driver_id']);
            });
        }

        $paginator = $query->paginate($perPage);

        // Eager-load lightweight relationships for list view
        $paginator->getCollection()->load([
            'warehouse:id,name',
            'driverTask:id,title,driver_id',
            'sender:id,first_name,last_name,full_name,phone',
            'status',
            'referenceType',
            'packageType',
        ]);

        return [
            'data' => $paginator->through(fn (Shipment $shipment) => $this->mapShipment($shipment))->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * Show a single shipment with eager-loaded relationships.
     *
     * @return array<string, mixed>
     *
     * @throws ModelNotFoundException
     */
    public function show(string $id): array
    {
        $shipment = Shipment::query()->find($id);

        if ($shipment === null) {
            throw (new ModelNotFoundException)->setModel(Shipment::class, $id);
        }

        // Eager-load relationships for detail view
        $shipment->load([
            'warehouse',
            'driverTask',
            'sender',
            'status',
            'referenceType',
            'packageType',
            'trackingEvents' => fn ($query) => $query->latest('timestamp')->limit(50),
        ]);

        return $this->mapShipment($shipment);
    }

    /**
     * Create a new shipment.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Shipment
    {
        $data = $this->resolveCatalogIds($data);

        // Si viene sender_id pero no recipient_name, sincronizar desde el cliente
        if (! empty($data['sender_id']) && empty($data['recipient_name'])) {
            $client = \App\Models\Client::find($data['sender_id']);
            if ($client) {
                $data['recipient_name'] = $client->full_name ?? trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
                if (empty($data['recipient_phone'])) {
                    $data['recipient_phone'] = $client->phone;
                }
            }
        }

        return Shipment::create($data);
    }

    /**
     * Update an existing shipment (partial update).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(string $id, array $data): Shipment
    {
        $shipment = Shipment::query()->find($id);

        if ($shipment === null) {
            throw (new ModelNotFoundException)->setModel(Shipment::class, $id);
        }

        $data = $this->resolveCatalogIds($data);

        $shipment->fill($data)->save();

        return $shipment;
    }

    /**
     * Delete a shipment if it has no related records.
     *
     * @throws ModelNotFoundException
     * @throws ShipmentHasRelationsException
     */
    public function delete(string $id): void
    {
        $shipment = Shipment::query()->find($id);

        if ($shipment === null) {
            throw (new ModelNotFoundException)->setModel(Shipment::class, $id);
        }

        if ($shipment->trackingEvents()->exists()) {
            throw new ShipmentHasRelationsException($shipment->id, 'tracking events');
        }

        if ($shipment->manifestItems()->exists()) {
            throw new ShipmentHasRelationsException($shipment->id, 'manifest items');
        }

        if ($shipment->shipmentTaskItems()->exists()) {
            throw new ShipmentHasRelationsException($shipment->id, 'shipment task items');
        }

        $shipment->delete();
    }

    /**
     * Get shipment statistics/KPIs grouped by status and total.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, by_status: array<string, int>, summary: array<string, int>}
     */
    public function getStats(array $filters = []): array
    {
        $baseQuery = Shipment::query();

        if (! empty($filters['warehouse_id'] ?? null)) {
            $baseQuery->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['date_from'] ?? null)) {
            $baseQuery->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'] ?? null)) {
            $baseQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $total = (clone $baseQuery)->count();

        $catalogoService = app(CatalogoService::class);
        $statuses = $catalogoService->getValoresBySlug('estado-envio');

        $countsByCode = [];
        foreach ($statuses as $status) {
            $countsByCode[$status->codigo] = 0;
        }

        // Count grouped by status_id
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
        $inWarehouse = $countsByCode['EN_BODEGA'] ?? 0;
        $assigned = $countsByCode['ASIGNADO'] ?? 0;
        $inTransit = $countsByCode['EN_TRANSITO'] ?? 0;
        $delivered = $countsByCode['ENTREGADO'] ?? 0;
        $returned = $countsByCode['DEVUELTO'] ?? 0;
        $failed = $countsByCode['FALLIDO'] ?? 0;
        $cancelled = $countsByCode['CANCELADO'] ?? 0;

        return [
            'total' => $total,
            'by_status' => $countsByCode,
            'summary' => [
                'pending' => $pending,
                'in_warehouse' => $inWarehouse,
                'assigned' => $assigned,
                'in_transit' => $inTransit,
                'active_in_transit' => $assigned + $inTransit,
                'delivered' => $delivered,
                'returned' => $returned,
                'failed' => $failed,
                'cancelled' => $cancelled,
                'issues' => $failed + $returned + $cancelled,
            ],
        ];
    }

    /**
     * Resuelve claves o códigos de catálogo a sus respectivos IDs en base de datos.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveCatalogIds(array $data): array
    {
        $catalogoService = app(CatalogoService::class);

        if (isset($data['status']) && empty($data['status_id'])) {
            $data['status_id'] = is_numeric($data['status'])
                ? (int) $data['status']
                : $catalogoService->getValorIdByCodigo('estado-envio', (string) $data['status']);
            unset($data['status']);
        }

        if (isset($data['reference_type']) && empty($data['reference_type_id'])) {
            $data['reference_type_id'] = is_numeric($data['reference_type'])
                ? (int) $data['reference_type']
                : $catalogoService->getValorIdByCodigo('tipo-referencia', (string) $data['reference_type']);
            unset($data['reference_type']);
        }

        if (isset($data['package_type']) && empty($data['package_type_id'])) {
            $data['package_type_id'] = is_numeric($data['package_type'])
                ? (int) $data['package_type']
                : $catalogoService->getValorIdByCodigo('tipo-paquete', (string) $data['package_type']);
            unset($data['package_type']);
        }

        return $data;
    }

    /**
     * Map a Shipment model to an array, including loaded relationships.
     *
     * @return array<string, mixed>
     */
    private function mapShipment(Shipment $shipment): array
    {
        $data = $shipment->toArray();

        if ($shipment->relationLoaded('warehouse') && $shipment->warehouse) {
            $data['warehouse'] = $shipment->warehouse->toArray();
            $data['warehouse_name'] = $shipment->warehouse->name;
        }

        if ($shipment->relationLoaded('driverTask') && $shipment->driverTask) {
            $data['assigned_task'] = $shipment->driverTask->toArray();
            $data['task_title'] = $shipment->driverTask->title;
        }

        if ($shipment->relationLoaded('status') && $shipment->status) {
            $statusModel = $shipment->getRelation('status');
            $statusCode = $statusModel?->codigo ?? (string) $shipment->status;
            $data['status'] = $statusCode;
            $data['status_data'] = $statusModel ? $statusModel->toArray() : ['codigo' => $statusCode, 'valor' => $statusCode];
            $data['status_code'] = $statusCode;
            $data['status_label'] = $statusModel?->valor ?? $statusCode;
            $data['status_metadata'] = $statusModel?->metadata ?? [];
        } else {
            $data['status'] = '';
            $data['status_code'] = '';
            $data['status_label'] = '';
            $data['status_metadata'] = [];
        }

        if ($shipment->relationLoaded('referenceType') && $shipment->referenceType) {
            $refModel = $shipment->getRelation('referenceType');
            $refCode = $refModel?->codigo ?? (string) $shipment->referenceType;
            $data['reference_type'] = strtolower($refCode);
            $data['reference_type_data'] = $refModel ? $refModel->toArray() : ['codigo' => $refCode, 'valor' => $refCode];
            $data['reference_type_code'] = $refCode;
            $data['reference_type_label'] = $refModel?->valor ?? $refCode;
            $data['reference_type_metadata'] = $refModel?->metadata ?? [];
        }

        if ($shipment->relationLoaded('packageType') && $shipment->packageType) {
            $pkgModel = $shipment->getRelation('packageType');
            $pkgCode = $pkgModel?->codigo ?? (string) $shipment->packageType;
            $data['package_type'] = strtolower($pkgCode);
            $data['package_type_data'] = $pkgModel ? $pkgModel->toArray() : ['codigo' => $pkgCode, 'valor' => $pkgCode];
            $data['package_type_code'] = $pkgCode;
            $data['package_type_label'] = $pkgModel?->valor ?? $pkgCode;
            $data['package_type_metadata'] = $pkgModel?->metadata ?? [];
        }

        $clientName = $shipment->recipient_name;
        $clientPhone = $shipment->recipient_phone;

        if ($shipment->relationLoaded('sender') && $shipment->sender) {
            $data['sender'] = $shipment->sender->toArray();
            $data['client'] = $shipment->sender->toArray();
            $clientName = $clientName ?: ($shipment->sender->full_name
                ?? trim(($shipment->sender->first_name ?? '').' '.($shipment->sender->last_name ?? '')));
            $clientPhone = $clientPhone ?: ($shipment->sender->phone ?? '');
        }

        $data['recipient_name'] = $clientName ?: 'Sin destinatario';
        $data['client_name'] = $clientName ?: 'Sin destinatario';
        $data['recipient_phone'] = $clientPhone ?: '';
        $data['client_phone'] = $clientPhone ?: '';

        if ($shipment->relationLoaded('trackingEvents')) {
            $data['tracking_events'] = $shipment->trackingEvents->toArray();
        }

        return $data;
    }
}
