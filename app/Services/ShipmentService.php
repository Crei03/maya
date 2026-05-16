<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ShipmentHasRelationsException;
use App\Models\Shipment;
use App\Repositories\ShipmentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShipmentService
{
    public function __construct(
        private readonly ShipmentRepository $repository,
    ) {}

    /**
     * List shipments with pagination and optional filters.
     *
     * @param array<string, mixed> $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function list(array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $paginator = $this->repository->paginateForTenant($perPage, $filters);

        // Eager-load lightweight relationships for list view (SC2)
        $paginator->getCollection()->load([
            'warehouse:id,name',
            'assignedTask:id,title',
        ]);

        return [
            'data' => $paginator->through(fn (Shipment $shipment) => $this->mapShipment($shipment))->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
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
        $shipment = $this->repository->findByIdForTenant($id);

        if ($shipment === null) {
            throw (new ModelNotFoundException())->setModel(Shipment::class, $id);
        }

        // Eager-load relationships for detail view
        $shipment->load([
            'warehouse',
            'assignedTask',
            'sender',
            'trackingEvents' => fn ($query) => $query->latest()->limit(50),
        ]);

        return $this->mapShipment($shipment);
    }

    /**
     * Create a new shipment.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Shipment
    {
        return Shipment::create($data);
    }

    /**
     * Update an existing shipment (partial update).
     *
     * @param array<string, mixed> $data
     *
     * @throws ModelNotFoundException
     */
    public function update(string $id, array $data): Shipment
    {
        $shipment = $this->repository->findByIdForTenant($id);

        if ($shipment === null) {
            throw (new ModelNotFoundException())->setModel(Shipment::class, $id);
        }

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
        $shipment = $this->repository->findByIdForTenant($id);

        if ($shipment === null) {
            throw (new ModelNotFoundException())->setModel(Shipment::class, $id);
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
     * Map a Shipment model to an array, including loaded relationships.
     *
     * @return array<string, mixed>
     */
    private function mapShipment(Shipment $shipment): array
    {
        $data = $shipment->toArray();

        // Include loaded relationships in the output (SC3)
        if ($shipment->relationLoaded('warehouse') && $shipment->warehouse) {
            $data['warehouse'] = $shipment->warehouse->toArray();
            $data['warehouse_name'] = $shipment->warehouse->name; // Convenience (SC2)
        }

        if ($shipment->relationLoaded('assignedTask') && $shipment->assignedTask) {
            $data['assigned_task'] = $shipment->assignedTask->toArray();
            $data['task_title'] = $shipment->assignedTask->title; // Convenience (SC2)
        }

        if ($shipment->relationLoaded('sender') && $shipment->sender) {
            $data['sender'] = $shipment->sender->toArray();
        }

        if ($shipment->relationLoaded('trackingEvents')) {
            $data['tracking_events'] = $shipment->trackingEvents->toArray();
        }

        return $data;
    }
}
