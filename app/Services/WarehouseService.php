<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WarehouseService
{
    /**
     * Paginate warehouses with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search   = trim((string) ($filters['search'] ?? ''));
        $isActive = $filters['is_active'] ?? null;

        return Warehouse::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($isActive !== null, fn ($q) => $q->where('is_active', (bool) $isActive))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find a warehouse by ID (scoped to current tenant via global scope).
     */
    public function find(string $id): Warehouse
    {
        return Warehouse::query()->findOrFail($id);
    }

    /**
     * Create a new warehouse.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Warehouse
    {
        $warehouse = Warehouse::query()->create([
            'name'             => $data['name'],
            'code'             => $data['code'],
            'location_address' => $data['location_address'] ?? null,
            'location_coords'  => $data['location_coords'] ?? null,
            'phone'            => $data['phone'] ?? null,
            'is_active'        => $data['is_active'] ?? true,
        ]);

        return $this->find($warehouse->id);
    }

    /**
     * Update an existing warehouse.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Warehouse
    {
        $warehouse = $this->find($id);

        $warehouse->fill([
            'name'             => $data['name'],
            'code'             => $data['code'],
            'location_address' => $data['location_address'] ?? $warehouse->location_address,
            'location_coords'  => $data['location_coords'] ?? $warehouse->location_coords,
            'phone'            => $data['phone'] ?? $warehouse->phone,
            'is_active'        => $data['is_active'] ?? $warehouse->is_active,
        ]);

        $warehouse->save();

        return $this->find($warehouse->id);
    }

    /**
     * Soft-delete a warehouse.
     */
    public function delete(string $id): bool
    {
        $warehouse = $this->find($id);

        return (bool) $warehouse->delete();
    }

    /**
     * Map a warehouse to the API shape used by the frontend.
     *
     * @return array<string, mixed>
     */
    public function mapWarehouse(Warehouse $warehouse): array
    {
        return [
            'id'               => $warehouse->id,
            'code'             => $warehouse->code,
            'name'             => $warehouse->name,
            'location_address' => $warehouse->location_address,
            'location_coords'  => $warehouse->location_coords,
            'phone'            => $warehouse->phone,
            'is_active'        => $warehouse->is_active,
            'created_at'       => $warehouse->created_at?->toISOString(),
        ];
    }
}
