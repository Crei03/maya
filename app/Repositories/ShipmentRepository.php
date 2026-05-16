<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Shipment;
use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ShipmentRepository
{
    use HasTenantScope;

    /**
     * Find a shipment by ID scoped to the current tenant.
     *
     * Returns null if the shipment doesn't exist OR belongs to a different tenant.
     * Cross-tenant and not-found are indistinguishable by design (SR2).
     */
    public function findByIdForTenant(string $id): ?Shipment
    {
        return $this->scopedQuery(Shipment::query())->find($id);
    }

    /**
     * Get all shipments for the current tenant.
     */
    public function allForTenant(): Collection
    {
        return $this->scopedQuery(Shipment::query())->get();
    }

    /**
     * Paginate shipments for the current tenant with optional filters.
     *
     * @param int   $perPage Number of items per page
     * @param array<string, mixed> $filters Optional filters (status, search, etc.)
     */
    public function paginateForTenant(int $perPage, array $filters): LengthAwarePaginator
    {
        $query = $this->scopedQuery(Shipment::query());

        if (! empty($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }
}
