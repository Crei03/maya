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
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = $filters['type'] ?? null;
        $isActive = $filters['is_active'] ?? null;

        return Vehicle::query()
            ->with(['ownershipType', 'vehicleClass'])
            ->when($search !== '', fn ($q) => $q->where('license_plate', 'like', "%{$search}%"))
            ->when(filled($type), function ($q) use ($type) {
                if (is_numeric($type)) {
                    $q->where('ownership_type_id', (int) $type);
                } else {
                    $code = strtoupper($type) === 'EXTERNAL' ? 'EXTERNO' : 'INTERNO';
                    $q->whereHas('ownershipType', fn ($sub) => $sub->where('codigo', $code));
                }
            })
            ->when($isActive !== null, fn ($q) => $q->where('is_active', (bool) $isActive))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find a vehicle by ID (scoped to current tenant via global scope).
     */
    public function find(string $id): Vehicle
    {
        return Vehicle::query()->with(['ownershipType', 'vehicleClass'])->findOrFail($id);
    }

    /**
     * Create a new vehicle.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vehicle
    {
        $data = $this->resolveCatalogIds($data);

        $vehicle = Vehicle::query()->create([
            'license_plate' => $data['license_plate'],
            'ownership_type_id' => $data['ownership_type_id'] ?? null,
            'vehicle_class_id' => $data['vehicle_class_id'] ?? null,
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'],
            'capacity_kg' => $data['capacity_kg'] ?? null,
            'capacity_volume' => $data['capacity_volume'] ?? null,
            'color' => $data['color'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->find($vehicle->id);
    }

    /**
     * Update an existing vehicle.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Vehicle
    {
        $vehicle = $this->find($id);
        $data = $this->resolveCatalogIds($data);

        $vehicle->fill([
            'license_plate' => $data['license_plate'] ?? $vehicle->license_plate,
            'ownership_type_id' => $data['ownership_type_id'] ?? $vehicle->ownership_type_id,
            'vehicle_class_id' => $data['vehicle_class_id'] ?? $vehicle->vehicle_class_id,
            'brand' => $data['brand'] ?? $vehicle->brand,
            'model' => $data['model'] ?? $vehicle->model,
            'year' => $data['year'] ?? $vehicle->year,
            'capacity_kg' => array_key_exists('capacity_kg', $data) ? $data['capacity_kg'] : $vehicle->capacity_kg,
            'capacity_volume' => array_key_exists('capacity_volume', $data) ? $data['capacity_volume'] : $vehicle->capacity_volume,
            'color' => array_key_exists('color', $data) ? $data['color'] : $vehicle->color,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $vehicle->is_active,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $vehicle->notes,
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

    private function resolveCatalogIds(array $data): array
    {
        $catalogoService = app(CatalogoService::class);

        if (isset($data['type']) && empty($data['ownership_type_id'])) {
            $code = strtoupper((string) $data['type']) === 'EXTERNAL' ? 'EXTERNO' : 'INTERNO';
            $data['ownership_type_id'] = $catalogoService->getValorIdByCodigo('tipo-vehiculo-propiedad', $code);
        }

        if (isset($data['vehicle_class']) && empty($data['vehicle_class_id'])) {
            $data['vehicle_class_id'] = $catalogoService->getValorIdByCodigo('clase-vehiculo', (string) $data['vehicle_class']);
        }

        return $data;
    }

    /**
     * Map a vehicle to the API shape used by the frontend.
     *
     * @return array<string, mixed>
     */
    public function mapVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'ownership_type_id' => $vehicle->ownership_type_id,
            'ownership_type' => $vehicle->ownershipType?->toArray(),
            'vehicle_class_id' => $vehicle->vehicle_class_id,
            'vehicle_class' => $vehicle->vehicleClass?->toArray(),
            'vehicle_class_label' => $vehicle->vehicleClass?->valor,
            'type' => $vehicle->ownershipType?->codigo === 'EXTERNO' ? 'external' : 'internal',
            'type_label' => $vehicle->getTypeLabel(),
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'capacity_kg' => $vehicle->capacity_kg,
            'capacity_volume' => $vehicle->capacity_volume,
            'color' => $vehicle->color,
            'is_active' => $vehicle->is_active,
            'notes' => $vehicle->notes,
            'created_at' => $vehicle->created_at?->toISOString(),
        ];
    }
}
