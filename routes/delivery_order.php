<?php

use App\Modules\DeliveryOrder\Controllers\DeliveryOrderController;
use Illuminate\Support\Facades\Route;

/**
 * Nama route harus memenuhi yang dipakai menu & approval:
 *   delivery-order.index   -> /delivery-order/documents (daftar)
 *   delivery-order.create  -> form buat DO
 *   delivery-order.store   -> submit DO
 *   delivery-order.show    -> detail DO
 *
 * Route statis didaftarkan sebelum `/{id}` supaya `/products` dan
 * `/create` tidak tertangkap sebagai id.
 */
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->name('delivery-order.')
    ->prefix('delivery-order')
    ->group(function () {
        Route::get('/documents', [DeliveryOrderController::class, 'index'])->name('index');
        Route::get('/products', [DeliveryOrderController::class, 'getProducts'])->name('products');
        Route::get('/form-options', [DeliveryOrderController::class, 'getFormOptions'])->name('form-options');
        // Revisi memakai nomor DO yang sama, jadi tidak ada resource baru:
        // URL formnya menempel pada dokumen yang direvisi, dan `PUT` berakhir
        // di `show` yang sama seperti setelah create. Daftarkan sebelum `/{id}`
        // supaya `/revisions` tidak tertangkap sebagai id.
        Route::get('/revisions', [DeliveryOrderController::class, 'indexRevisions'])->name('revisions');
        Route::get('/{id}/revise', [DeliveryOrderController::class, 'revise'])->name('revise');
        Route::put('/{id}/revise', [DeliveryOrderController::class, 'updateRevision'])->name('revise.update');
        Route::get('/create', [DeliveryOrderController::class, 'create'])->name('create');
        Route::post('/store', [DeliveryOrderController::class, 'store'])->name('store');
        Route::get('/{id}/approvals', [DeliveryOrderController::class, 'getApprovals'])->name('approvals');
        Route::get('/{id}', [DeliveryOrderController::class, 'show'])->name('show');
        // Link lama / tanpa suffix: arahkan ke daftar dokumen.
        Route::redirect('/', '/delivery-order/documents');
    });
