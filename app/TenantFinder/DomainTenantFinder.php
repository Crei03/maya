<?php

declare(strict_types=1);

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        // If multi-tenant is disabled, use demo tenant
        if (! config('multi-tenant.enabled')) {
            return Tenant::query()->where('slug', 'demo')->first();
        }

        $host = $request->getHost();

        // If on root domain (maya.app), check if user is authenticated and use their tenant
        if (! str_contains($host, '.') || explode('.', $host)[0] === 'www') {
            $user = $request->user();
            if ($user && $user->tenant_id) {
                return Tenant::find($user->tenant_id);
            }

            return null;
        }

        $slug = explode('.', $host)[0];

        return Tenant::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }
}
