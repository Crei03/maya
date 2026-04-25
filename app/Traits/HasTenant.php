<?php

declare(strict_types=1);

namespace App\Traits;

use Spatie\Multitenancy\Models\Tenant;

trait HasTenant
{
    /**
     * Boot the trait.
     * Automatically assigns tenant_id from current tenant when creating.
     */
    public static function bootHasTenant(): void
    {
        static::creating(function ($model): void {
            if (empty($model->tenant_id)) {
                $tenant = Tenant::current();
                
                // If no tenant is current, try to use demo tenant (for local dev)
                if (! $tenant && ! config('multi-tenant.enabled')) {
                    $tenant = Tenant::query()->where('slug', 'demo')->first();
                    if ($tenant) {
                        $tenant->makeCurrent();
                    }
                }
                
                if ($tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }
}
