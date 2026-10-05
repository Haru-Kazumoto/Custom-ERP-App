<?php

namespace App\Modules\PurchaseOrder\DTOs;

use Illuminate\Http\Request;

class CreatePurchaseOrderDTO
{
    /**
     * @param  PurchaseOrderDetailDTO[]  $details
     * @param  PurchaseOrderItemDTO[]  $items
     */
    public function __construct(
        public readonly string $document_code,
        public readonly int $term_of_payment,
        public readonly string $due_date,
        public readonly ?string $description,
        public readonly bool $use_tax,
        public readonly array $details,
        public readonly array $items,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $details = collect($request->transaction_details)
            ->map(fn ($detail) => new PurchaseOrderDetailDTO(
                name: (string) $detail['name'],
                type: (string) $detail['type'],
                value: $detail['value'],
            ))
            ->all();

        $items = collect($request->transaction_items)
            ->map(fn ($item) => new PurchaseOrderItemDTO(
                product_id: (int) $item['product_id'],
                quantity: (int) $item['quantity'],
                // Harga bruto per satuan (sudah termasuk PPN). Angka inilah yang
                // dipakai server untuk menghitung `base_price`, `sub_total`,
                // `tax_amount`, dan `grand_total` — jadi frontend tidak perlu
                // mengirim subtotalnya sendiri.
                unit_price: (float) $item['unit_price'],
                trade_promo_id: $item['trade_promo_id'] !== null
                    ? (int) $item['trade_promo_id']
                    : null,
            ))
            ->all();

        return new self(
            document_code: (string) $request->document_code,
            term_of_payment: (int) $request->term_of_payment,
            // Kolom `transactions.due_date` bertipe DATE, sementara NDatePicker
            // mengirim `YYYY-MM-DDTHH:mm`. Jam sudah dihitung di form
            // (`term_of_payment`), jadi bagian waktu dibuang di sini.
            due_date: substr((string) $request->due_date, 0, 10),
            description: $request->description !== null
                ? (string) $request->description
                : null,
            use_tax: self::extractUseTax($details),
            details: $details,
            items: $items,
        );
    }

    /**
     * Status PPN diambil dari detail bertipe `USE_TAX`, bukan field terpisah.
     *
     * Kalau dikirim dua kali (field terpisah + detail) keduanya bisa berbeda dan
     * server tidak tahu mana yang benar. `transaction_details` sudah jadi
     * tempat nilai-nilai PO disimpan, jadi dipakai sebagai satu-satunya sumber.
     */
    private static function extractUseTax(array $details): bool
    {
        $useTax = collect($details)
            ->first(fn ($detail) => $detail->type === 'USE_TAX');

        return $useTax === null
            ? true
            : filter_var($useTax->value, FILTER_VALIDATE_BOOLEAN);
    }
}
