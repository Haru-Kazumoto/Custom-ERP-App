<?php

use App\Modules\Dashboard\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// default route
Route::redirect('/', '/dashboard');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

require __DIR__.'/approvals.php';
require __DIR__.'/purchase_order.php';
require __DIR__.'/sub_sales_order.php';
require __DIR__.'/product.php';
require __DIR__.'/menus.php';
