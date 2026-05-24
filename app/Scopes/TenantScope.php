<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = Tenant::current();

        // If no tenant is current, try to use demo tenant (for local dev / tests)
        if (! $tenant && ! config('multi-tenant.enabled')) {
            $tenant = Tenant::query()->where('slug', 'demo')->first();
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        if ($tenant) {
            $builder->where($model->getTable() . '.tenant_id', $tenant->id);
        }
    }
}
