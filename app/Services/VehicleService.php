<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleService
{
    /**
     * Paginate vehicles with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search   = trim((string) ($filters['search'] ?? ''));
        $type     = $filters['type'] ?? null;
        $isActive = $filters['is_active'] ?? null;

        return Vehicle::query()
            ->when($search !== '', fn ($q) => $q->where('license_plate', 'like', "%{$search}%"))
            ->when(filled($type), fn ($q) => $q->where('type', $type))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', (bool) $isActive))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find a vehicle by ID (scoped to current tenant via global scope).
     */
    public function find(string $id): Vehicle
    {
        return Vehicle::query()->findOrFail($id);
    }

    /**
     * Create a new vehicle.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Vehicle
    {
        $vehicle = Vehicle::query()->create([
            'license_plate'   => $data['license_plate'],
            'type'            => $data['type'],
            'brand'           => $data['brand'],
            'model'           => $data['model'],
            'year'            => $data['year'],
            'capacity_kg'     => $data['capacity_kg'] ?? null,
            'capacity_volume' => $data['capacity_volume'] ?? null,
            'color'           => $data['color'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
            'notes'           => $data['notes'] ?? null,
        ]);

        return $this->find($vehicle->id);
    }

    /**
     * Update an existing vehicle.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Vehicle
    {
        $vehicle = $this->find($id);

        $vehicle->fill([
            'license_plate'   => $data['license_plate'],
            'type'            => $data['type'],
            'brand'           => $data['brand'],
            'model'           => $data['model'],
            'year'            => $data['year'],
            'capacity_kg'     => $data['capacity_kg'] ?? null,
            'capacity_volume' => $data['capacity_volume'] ?? null,
            'color'           => $data['color'] ?? null,
            'is_active'       => $data['is_active'] ?? $vehicle->is_active,
            'notes'           => $data['notes'] ?? null,
        ]);

        $vehicle->save();

        return $this->find($vehicle->id);
    }

    /**
     * Soft-delete a vehicle.
     */
    public function delete(string $id): bool
    {
        $vehicle = $this->find($id);

        return (bool) $vehicle->delete();
    }

    /**
     * Map a vehicle to the API shape used by the frontend.
     *
     * @return array<string, mixed>
     */
    public function mapVehicle(Vehicle $vehicle): array
    {
        return [
            'id'              => $vehicle->id,
            'license_plate'   => $vehicle->license_plate,
            'type'            => $vehicle->type,
            'type_label'      => $vehicle->getTypeLabel(),
            'brand'           => $vehicle->brand,
            'model'           => $vehicle->model,
            'year'            => $vehicle->year,
            'capacity_kg'     => $vehicle->capacity_kg,
            'capacity_volume' => $vehicle->capacity_volume,
            'color'           => $vehicle->color,
            'is_active'       => $vehicle->is_active,
            'notes'           => $vehicle->notes,
            'created_at'      => $vehicle->created_at?->toISOString(),
        ];
    }
}
