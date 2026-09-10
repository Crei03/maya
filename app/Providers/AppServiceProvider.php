<?php

namespace App\Providers;

use App\Models\Tenant;
use App\TenantFinder\DomainTenantFinder;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
