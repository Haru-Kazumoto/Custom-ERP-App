<?php

use App\Modules\Product\Controllers\ProductController;
use App\Modules\Product\Controllers\ProductManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('api/product')
    ->name('product.')
    ->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
    });

// Halaman manajemen produk. Prefix dan nama route (`products.*`) sengaja
// dipisah dari grup JSON di atas (`product.*`) supaya keduanya tidak bentrok.
//
// Daftar ada di `/products/list` karena url itu yang dipakai baris menu
// "Daftar Produk" di tabel `menus`.
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('products')
    ->name('products.')
    ->group(function () {
        Route::get('/list', [ProductManagementController::class, 'index'])->name('index');
        Route::get('/create', [ProductManagementController::class, 'create'])->name('create');
        Route::post('/store', [ProductManagementController::class, 'store'])->name('store');
        Route::get('/{product}', [ProductManagementController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [ProductManagementController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductManagementController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductManagementController::class, 'destroy'])->name('destroy');
    });
