<?php

declare(strict_types=1);

use App\Http\Controllers\Management\AuditLogController;
use App\Http\Controllers\Management\BlogController;
use App\Http\Controllers\Management\CatalogoController;
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
    ->prefix('management')
    ->name('Management.')
    ->group(function () {
        Route::get('/dashboard', [ManagementDashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('tenants', TenantController::class)
            ->except(['destroy']);

        Route::patch('/tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])
            ->name('tenants.toggle-status');

        Route::resource('blog', BlogController::class)
            ->except(['show'])
            ->parameters(['blog' => 'post']);

        Route::post('/blog/{post}/publish', [BlogController::class, 'publish'])
            ->name('blog.publish');

        Route::post('/blog/analyze-seo', [BlogController::class, 'analyzeSeo'])
            ->name('blog.analyze-seo');

        Route::post('/blog/upload-image', [BlogController::class, 'uploadImage'])
            ->name('blog.upload-image');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::resource('catalogos', CatalogoController::class);

        Route::post('/catalogos/{catalogo}/valores', [CatalogoController::class, 'storeValor'])
            ->name('catalogos.valores.store');
        Route::put('/catalogos/{catalogo}/valores/{valor}', [CatalogoController::class, 'updateValor'])
            ->name('catalogos.valores.update');
        Route::delete('/catalogos/{catalogo}/valores/{valor}', [CatalogoController::class, 'destroyValor'])
            ->name('catalogos.valores.destroy');
    });
