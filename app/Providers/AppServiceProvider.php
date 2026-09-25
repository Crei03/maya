<?php

namespace App\Providers;

use App\Models\Tenant;
use App\TenantFinder\DomainTenantFinder;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // Configure Spatie multitenancy tenant finder
        config()->set('multitenancy.tenant_finder', DomainTenantFinder::class);
        config()->set('multitenancy.tenant_model', Tenant::class);
    }
}
