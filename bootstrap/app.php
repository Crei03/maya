<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (config('multi-tenant.enabled')) {
                // Tenant-scoped routes under subdomain (production)
                Route::middleware('web')
                    ->domain('{tenant}.'.parse_url(config('app.url'), PHP_URL_HOST))
                    ->group(base_path('routes/admin.php'));
            } else {
                // Single-tenant mode: register admin routes on main domain
                Route::middleware('web')
                    ->group(base_path('routes/admin.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if ($user?->isManagement()) {
                return route('Management.dashboard', absolute: false);
            }
            if ($user?->isMessenger()) {
                return route('driver.dashboard', absolute: false);
            }

            return route('admin.dashboard', absolute: false);
        });

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureTenant::class,
            'Management' => \App\Http\Middleware\EnsureManagement::class,
            'gestor' => \App\Http\Middleware\EnsureGestor::class,
            'driver' => \App\Http\Middleware\EnsureDriver::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
