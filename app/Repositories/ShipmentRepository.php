<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Shipment;
use App\Traits\HasTenantScope;
use Illuminate\Database\Eloquent\Builder;
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

        if (! empty($filters['search'] ?? null)) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['warehouse_id'] ?? null)) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['package_type'] ?? null)) {
            $query->where('package_type', $filters['package_type']);
        }

        if (! empty($filters['date_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['driver_id'] ?? null)) {
            $query->whereHas('assignedTask', function (Builder $q) use ($filters): void {
                $q->where('driver_id', $filters['driver_id']);
            });
        }

        return $query->paginate($perPage);
    }
}
