<?php

use App\Modules\SubSalesOrder\Controllers\SubSalesOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('sub-sales-order.')
    ->prefix('sub-sales-orders')
    ->group(function () {
        Route::get('', [SubSalesOrderController::class, 'index'])->name('index');
        Route::get('/create', [SubSalesOrderController::class, 'create'])->name('create');
        Route::post('/store', [SubSalesOrderController::class, 'store'])->name('store');
    });
