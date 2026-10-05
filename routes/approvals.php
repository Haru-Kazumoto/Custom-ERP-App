<?php

use App\Modules\Approval\Controllers\ApprovalController;
use Illuminate\Support\Facades\Route;

/**
 * Nama route di sini harus sama dengan `route_name` di tabel `menus`:
 *   approval_documents          -> approvals.index
 *   purchase-order-approvals    -> approvals.index.purchase-orders
 *   delivery-order-approvals    -> approvals.index.delivery-orders
 *
 * `delivery-orders` sengaja belum ada karena modul Delivery Order belum ada;
 * menu-nya masih akan 404 sampai modulnya dikerjakan.
 */
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('approvals.')
    ->prefix('approvals')
    ->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/purchase-orders', [ApprovalController::class, 'purchaseOrders'])->name('index.purchase-orders');
        Route::post('/purchase-orders/{transaction}/decision', [ApprovalController::class, 'decidePurchaseOrder'])
            ->name('purchase-orders.decision');
    });
