<?php

use App\Http\Controllers\UsersController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/users', [UsersController::class, 'edit'])->name('users.edit');
    Route::patch('/users', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users', [UsersController::class, 'destroy'])->name('users.destroy');
});

require __DIR__.'/auth.php';

// Admin routes
require __DIR__.'/admin.php';
