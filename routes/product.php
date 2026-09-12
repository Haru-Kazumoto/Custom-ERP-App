<?php

use App\Modules\Product\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('api/product')
    ->name('product.')
    ->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
    });