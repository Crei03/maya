<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BlogPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->group(function () {
    Route::get('/posts', [BlogPostController::class, 'index']);
    Route::get('/posts/{slug}', [BlogPostController::class, 'show']);
});
