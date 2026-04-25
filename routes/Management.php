<?php

declare(strict_types=1);

use App\Http\Controllers\Management\AuditLogController;
use App\Http\Controllers\Management\ManagementDashboardController;
use App\Http\Controllers\Management\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes (Landlady)
|--------------------------------------------------------------------------
|
| These routes are accessible only on the root domain (maya.app)
| and require super_admin role. They manage the SaaS platform itself.
|
*/

Route::middleware(['auth', 'Management'])
    ->domain(config('app.url'))
    ->prefix('super-admin')
    ->name('Management.')
    ->group(function () {
        Route::get('/dashboard', [ManagementDashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('tenants', TenantController::class)
            ->except(['destroy']);

        Route::patch('/tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])
            ->name('tenants.toggle-status');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
    });
