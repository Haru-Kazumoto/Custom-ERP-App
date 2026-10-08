<?php

namespace App\Modules\DeliveryOrder\DTOs;

use Illuminate\Http\Request;

/**
 * Data revisi Delivery Order.
 *
 *extends {@see CreateDeliveryOrderDTO} karena isinya sama persis — bedanya
 * hanya satu, dan itu bagian pentingnya: `document_code` TIDAK ada di sini.
 * Nomor DO adalah identitas dokumen. Kalau revisi boleh mengganti nomor,
 * maka approval yang sudah diberikan untuk nomor lama tidak lagi berlaku
 * untuk dokumen yang sama, dan `transaction_approval_histories` yang
 * dirujuk orang saat audit jadi menunjuk nomor yang sudah tidak ada.
 *
 * Nomor yang dipakai saat revisi selalu diambil ulang dari baris
 * `transactions` di database, bukan dari kiriman browser —
 * `ReviseDeliveryOrderAction` sengaja tidak pernah menyentuh kolom
 * `transaction_code`.
 *
 * Seluruh payload lain (tanggal kirim, gudang, pelanggan, segmen, barang)
 * tetap divalidasi ulang oleh controller terhadap aturan yang sama dengan
 * `store`, karena nominalnya dihitung ulang di server dari data terbaru.
 */
class ReviseDeliveryOrderDTO extends CreateDeliveryOrderDTO
{
    /**
     * `document_code` diisi string kosong oleh parent constructor. Nilai ini
     * tidak pernah dibaca saat revisi.
     *
     * Parameter `paymentTerm`/`dueDate`/`customerSegment` sengaja diambil
     * alih-alih dibaca dari request: termin pelanggan dan jatuh tempo
     * dihitung ulang server dari `customers.term_payment`, dan segmen
     * divalidasi whitelist — sama persis dengan cara `store()` membangun
     * `CreateDeliveryOrderDTO`, supaya jalur create dan revisi tidak bisa
     * berbeda angka.
     */
    public static function fromRequest(
        Request $request,
        int $paymentTerm,
        string $dueDate,
        ?string $customerSegment,
    ): self {
        $create = CreateDeliveryOrderDTO::fromRequest($request, $paymentTerm, $dueDate, $customerSegment);

        return new self(
            document_code: '',
            delivery_date: $create->delivery_date,
            due_date: $create->due_date,
            payment_term: $create->payment_term,
            description: $create->description,
            use_tax: $create->use_tax,
            company_id: $create->company_id,
            shipping_id: $create->shipping_id,
            sub_shipping_id: $create->sub_shipping_id,
            customer_id: $create->customer_id,
            customer_segment: $create->customer_segment,
            details: $create->details,
            items: $create->items,
        );
    }
}
