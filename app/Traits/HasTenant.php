<?php

declare(strict_types=1);

namespace App\Traits;

// IMPORTANT: Must use App\Models\Tenant (not Spatie's base class).
// Spatie stores the current tenant under a fixed container key.
// If EnsureTenant sets a Spatie base-class instance (which has $incrementing = true),
// App\Models\Tenant::current() receives it but PHP strict return-types reject it
// because the instance is not App\Models\Tenant. Keeping the Spatie import here
// is fine because by the time creating() fires, the container already holds
// the correct App\Models\Tenant instance set by EnsureTenant middleware.
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
