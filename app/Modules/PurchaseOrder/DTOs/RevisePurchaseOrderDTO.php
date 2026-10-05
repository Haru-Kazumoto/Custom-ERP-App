<?php

namespace App\Modules\PurchaseOrder\DTOs;

use Illuminate\Http\Request;

/**
 * Data revisi Purchase Order.
 *
 *extends {@see CreatePurchaseOrderDTO} karena isinya sama persis — bedanya
 *hanya satu, dan itu justru bagian pentingnya: `document_code` TIDAK ada di
 * sini. Nomor PO adalah identitas dokumen. Kalau revisi boleh mengganti nomor,
 * maka approve yang sudah diberikan untuk nomor lama tidak lagi berlaku untuk
 * dokumen yang sama, dan `transaction_approval_histories` yang dirujuk orang
 * saat audit jadi menunjuk nomor yang sudah tidak ada.
 *
 * Nomor yang dipakai saat revisi selalu diambil ulang dari baris
 * `transactions` di database, bukan dari kiriman browser.
 *
 * Extend-nya bukan sekadar hemat kode: `PurchaseOrderCalculator` dan
 * `PurchaseOrderRepository::create()` sudah menerima tipe
 * `CreatePurchaseOrderDTO`, jadi dengan turunan ini tidak perlu menduplikasi
 * perhitungan nominal untuk jalur revisi. Kalau nanti bentuk datanya memang
 * berbeda, pemecahan tipe di situ lebih jujur daripada memaksakan warisan.
 */
class RevisePurchaseOrderDTO extends CreatePurchaseOrderDTO
{
    /**
     * `document_code` diisi string kosong oleh parent constructor. Nilai ini
     * tidak pernah dibaca saat revisi; `RevisePurchaseOrderAction` sengaja
     * tidak pernah menyentuh kolom `transaction_code`.
     */
    public static function fromRequest(Request $request): self
    {
        $create = CreatePurchaseOrderDTO::fromRequest($request);

        return new self(
            document_code: '',
            term_of_payment: $create->term_of_payment,
            due_date: $create->due_date,
            description: $create->description,
            use_tax: $create->use_tax,
            details: $create->details,
            items: $create->items,
        );
    }
}
