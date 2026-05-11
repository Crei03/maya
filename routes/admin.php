<?php

use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\KPIController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ColumnPreferenceController;
use App\Http\Controllers\Admin\ManifestController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\VehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes (Tenant-scoped)
|--------------------------------------------------------------------------
|
| These routes are accessible under tenant subdomains ({slug}.maya.app)
| and require authentication with gestor or messenger roles.
| All routes enforce tenant isolation via TenantScope.
|
*/

Route::middleware(['auth', 'tenant', 'gestor'])
    ->group(function () {
        // Dashboard de KPIs
Route::get('/dashboard', [KPIController::class, 'dashboard'])
    ->name('admin.dashboard');

// Conciliación de Cierre
Route::get('/conciliacion-cierre', function () {
    return Inertia::render('Admin/ConciliacionCierre');
})->name('admin.conciliacion-cierre');

// Versión Mobile de Conciliación
Route::get('/conciliacion-cierre/mobile', function () {
    return Inertia::render('Admin/ConciliacionCierre/Mobile');
})->name('admin.conciliacion-cierre.mobile');

// Asignación de Transporte (Manifiestos)
Route::get('/asignacion-transporte', [ManifestController::class, 'index'])
    ->name('admin.asignacion-transporte');

// Configuración Admin
Route::get('/configuracion', [SettingsController::class, 'index'])
    ->name('admin.configuracion');

Route::get('/configuracion/clientes', [SettingsController::class, 'clients'])
    ->name('admin.configuracion.clientes');

Route::get('/configuracion/usuarios', [SettingsController::class, 'users'])
    ->name('admin.configuracion.usuarios');

Route::get('/configuracion/transportes', [VehicleController::class, 'page'])
    ->name('admin.configuracion.transportes');

Route::get('/configuracion/conductores', [SettingsController::class, 'drivers'])
    ->name('admin.configuracion.conductores');

// Versión Mobile de Asignación de Transporte
Route::get('/asignacion-transporte/mobile', [ManifestController::class, 'mobile'])
    ->name('admin.asignacion-transporte.mobile');

// API endpoints para Manifiestos
Route::prefix('api/manifests')->group(function () {
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
Route::prefix('api')->group(function () {
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

    Route::get('/users/all', [UsersController::class, 'listAll'])
        ->name('admin.users.all');

    Route::get('/users', [UsersController::class, 'list'])
        ->name('admin.users.list');

    Route::get('/users/{id}', [UsersController::class, 'show'])
        ->name('admin.users.show');

    Route::post('/users', [UsersController::class, 'store'])
        ->name('admin.users.store');

    Route::patch('/users/{id}', [UsersController::class, 'update'])
        ->name('admin.users.update');

    Route::delete('/users/{id}', [UsersController::class, 'destroy'])
        ->name('admin.users.destroy');

    Route::get('/catalogos/{slug}/valores', [ClientController::class, 'catalogValues'])
        ->name('admin.catalogos.valores');

    Route::get('/catalogos/pa/hierarchy', [ClientController::class, 'paHierarchy'])
        ->name('admin.catalogos.pa.hierarchy');

    Route::get('/column-preferences/{module}', [ColumnPreferenceController::class, 'show'])
        ->name('admin.column-preferences.show');

    Route::put('/column-preferences/{module}', [ColumnPreferenceController::class, 'update'])
        ->name('admin.column-preferences.update');

    // Vehículos / Transportes
    Route::get('/vehicles', [VehicleController::class, 'list'])
        ->name('admin.vehicles.list');
    Route::post('/vehicles', [VehicleController::class, 'store'])
        ->name('admin.vehicles.store');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])
        ->name('admin.vehicles.show');
    Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update'])
        ->name('admin.vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])
        ->name('admin.vehicles.destroy');

    // Conductores
    Route::get('/drivers', [DriverController::class, 'list'])
        ->name('admin.drivers.list');
    Route::post('/drivers', [DriverController::class, 'store'])
        ->name('admin.drivers.store');
    Route::get('/drivers/{driver}', [DriverController::class, 'show'])
        ->name('admin.drivers.show');
    Route::patch('/drivers/{driver}', [DriverController::class, 'update'])
        ->name('admin.drivers.update');
    Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])
        ->name('admin.drivers.destroy');
});
});
