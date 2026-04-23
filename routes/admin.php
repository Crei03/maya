<?php

use App\Http\Controllers\Admin\KPIController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ColumnPreferenceController;
use App\Http\Controllers\Admin\ManifestController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Rutas para el panel de administración de MAYA.
| Por ahora sin autenticación compleja - acceso directo como admin.
|
| TODO @future: Agregar middleware de autenticación y autorización
|
*/

// Dashboard de KPIs
Route::get('/admin/dashboard', [KPIController::class, 'dashboard'])
    ->name('admin.dashboard');

// Conciliación de Cierre
Route::get('/admin/conciliacion-cierre', function () {
    return Inertia::render('Admin/ConciliacionCierre');
})->name('admin.conciliacion-cierre');

// Versión Mobile de Conciliación
Route::get('/admin/conciliacion-cierre/mobile', function () {
    return Inertia::render('Admin/ConciliacionCierre/Mobile');
})->name('admin.conciliacion-cierre.mobile');

// Asignación de Transporte (Manifiestos)
Route::get('/admin/asignacion-transporte', [ManifestController::class, 'index'])
    ->name('admin.asignacion-transporte');

// Configuración Admin
Route::get('/admin/configuracion', [SettingsController::class, 'index'])
    ->name('admin.configuracion');

Route::get('/admin/configuracion/clientes', [SettingsController::class, 'clients'])
    ->name('admin.configuracion.clientes');

// Versión Mobile de Asignación de Transporte
Route::get('/admin/asignacion-transporte/mobile', [ManifestController::class, 'mobile'])
    ->name('admin.asignacion-transporte.mobile');

// API endpoints para Manifiestos
Route::prefix('api/admin/manifests')->group(function () {
    Route::get('/', [ManifestController::class, 'list'])
        ->name('admin.manifests.list');
    Route::post('/', [ManifestController::class, 'store'])
        ->name('admin.manifests.store');
    Route::get('/{id}', [ManifestController::class, 'show'])
        ->name('admin.manifests.show');
    Route::post('/{id}/iniciar-despacho', [ManifestController::class, 'iniciarDespacho'])
        ->name('admin.manifests.iniciar-despacho');
});

// API endpoints para KPIs
Route::prefix('api/admin')->group(function () {
    Route::get('/kpis', [KPIController::class, 'index'])
        ->name('admin.kpis.index');

    Route::get('/kpis/delivery-rate', [KPIController::class, 'deliveryRate'])
        ->name('admin.kpis.delivery-rate');

    Route::get('/kpis/satisfaction', [KPIController::class, 'satisfaction'])
        ->name('admin.kpis.satisfaction');

    Route::get('/kpis/by-messenger', [KPIController::class, 'byMessenger'])
        ->name('admin.kpis.by-messenger');

    Route::get('/clients', [ClientController::class, 'list'])
        ->name('admin.clients.list');

    Route::get('/clients/{id}', [ClientController::class, 'show'])
        ->name('admin.clients.show');

    Route::post('/clients', [ClientController::class, 'store'])
        ->name('admin.clients.store');

    Route::patch('/clients/{id}', [ClientController::class, 'update'])
        ->name('admin.clients.update');

    Route::delete('/clients/{id}', [ClientController::class, 'destroy'])
        ->name('admin.clients.destroy');

    Route::get('/catalogos/{slug}/valores', [ClientController::class, 'catalogValues'])
        ->name('admin.catalogos.valores');

    Route::get('/catalogos/pa/hierarchy', [ClientController::class, 'paHierarchy'])
        ->name('admin.catalogos.pa.hierarchy');

    Route::get('/column-preferences/{module}', [ColumnPreferenceController::class, 'show'])
        ->name('admin.column-preferences.show');

    Route::put('/column-preferences/{module}', [ColumnPreferenceController::class, 'update'])
        ->name('admin.column-preferences.update');
});
