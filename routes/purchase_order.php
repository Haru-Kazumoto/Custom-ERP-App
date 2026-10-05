<?php

use App\Modules\PurchaseOrder\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('purchase-order.')
    ->prefix('purchase-orders')
    ->group(function () {
        Route::get('/documents', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/transaction-codes', [PurchaseOrderController::class, 'getTransactionCodes'])->name('get-transaction-codes');
        Route::get('/revisions', [PurchaseOrderController::class, 'indexRevisions'])->name('revisions');
        // Revisi memakai nomor PO yang sama, jadi tidak ada resource baru: URL
        // formnya menempel pada dokumen yang direvisi, dan `PUT` berakhir di
        // `show` yang sama seperti setelah create.
        Route::get('/{id}/revise', [PurchaseOrderController::class, 'revise'])->name('revise');
        Route::put('/{id}/revise', [PurchaseOrderController::class, 'updateRevision'])->name('revise.update');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/store', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/get-by-transaction-code', [PurchaseOrderController::class, 'getByTransactionCode'])->name('get-by-transaction-code');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{id}/approvals', [PurchaseOrderController::class, 'getApprovals'])->name('approvals');
    });
