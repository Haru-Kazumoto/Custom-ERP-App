<?php

use App\Modules\Approval\Controllers\ApprovalController;
use Illuminate\Support\Facades\Route;

/**
 * Nama route di sini harus sama dengan `route_name` di tabel `menus`:
 *   approval_documents          -> approvals.index
 *   purchase-order-approvals    -> approvals.index.purchase-orders
 *   delivery-order-approvals    -> approvals.index.delivery-orders
 */
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('approvals.')
    ->prefix('approvals')
    ->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/purchase-orders', [ApprovalController::class, 'purchaseOrders'])->name('index.purchase-orders');
        Route::post('/purchase-orders/{transaction}/decision', [ApprovalController::class, 'decidePurchaseOrder'])
            ->name('purchase-orders.decision');
        Route::get('/delivery-orders', [ApprovalController::class, 'deliveryOrders'])->name('index.delivery-orders');
        Route::post('/delivery-orders/{transaction}/decision', [ApprovalController::class, 'decideDeliveryOrder'])
            ->name('delivery-orders.decision');
    });
