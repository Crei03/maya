<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\TenantFinder\DomainTenantFinder;
use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('EnsureTenant middleware', [
            'url' => $request->url(),
            'multi_tenant' => config('multi-tenant.enabled'),
            'user' => $request->user()?->email,
        ]);

        // Skip tenant check if multi-tenant is disabled
        if (! config('multi-tenant.enabled')) {
            // Still set current tenant to demo for data isolation
            $tenant = Tenant::query()->where('slug', 'demo')->first();
            if ($tenant) {
                $tenant->makeCurrent();
                \Log::info('Tenant set to demo', ['tenant_id' => $tenant->id]);
            }
            return $next($request);
        }

        $tenant = Tenant::current();

        if (! $tenant) {
            // Try to resolve tenant using DomainTenantFinder
            $finder = new DomainTenantFinder();
            $tenant = $finder->findForRequest($request);

            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        if (! $tenant) {
            abort(404, 'Tenant not found');
        }

        if ($tenant->isPaused()) {
            abort(403, 'This tenant has been paused. Please contact support.');
        }

        return $next($request);
    }
}
