<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\TenantFinder\DomainTenantFinder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Configure Spatie multitenancy tenant finder
        config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);
        config()->set('multitenancy.tenant_model', Tenant::class);
    }
}
