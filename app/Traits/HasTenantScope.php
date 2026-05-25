<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait HasTenantScope
{
    /**
     * Apply tenant scope to a query builder.
     *
     * Filters the query by the current tenant ID. When no tenant is current,
     * the filter uses null (tenant_id IS NULL), returning no results from
     * tenant-specific tables.
     */
    protected function scopedQuery(Builder $query): Builder
    {
        return $query->where('tenant_id', Tenant::current()?->id);
    }
}
