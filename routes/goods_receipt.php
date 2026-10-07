<?php

use App\Modules\GoodsReceipt\Controllers\GoodsReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('goods-receipt.')
    ->prefix('goods-receipt')
    ->group(function () {
        Route::get('', [GoodsReceiptController::class, 'index'])->name('index');
        Route::get('/transaction-codes', [GoodsReceiptController::class, 'getTransactionCodes'])->name('get-transaction-codes');
        Route::get('/get-by-transaction-code', [GoodsReceiptController::class, 'getByTransactionCode'])->name('get-by-transaction-code');
        Route::post('/store', [GoodsReceiptController::class, 'store'])->name('store');
    });
