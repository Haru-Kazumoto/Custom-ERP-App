<?php

use App\Modules\Stock\Controllers\StockController;
use Illuminate\Support\Facades\Route;

// Menu induk "Stok Gudang" di tabel menus masih menunjuk /warehouse-stocks
// — redirectkan ke daftar semua barang supaya tidak 404.
Route::redirect('/warehouse-stocks', '/stocks/all');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('stocks.')
    ->prefix('stocks')
    ->group(function () {
        Route::get('/all', [StockController::class, 'index'])->name('index');
        Route::get('/batches', [StockController::class, 'batches'])->name('index.batches');
        Route::get('/stagnations', [StockController::class, 'stagnations'])->name('index.stagnations');
        Route::get('/gradually', [StockController::class, 'gradually'])->name('index.gradually');
        Route::post('/graduals/{id}/arrive', [StockController::class, 'arrive'])
            ->whereNumber('id')
            ->name('gradually.arrive');
    });
