<?php

namespace App\Modules\DeliveryOrder\DTOs;

use Illuminate\Http\Request;

class CreateDeliveryOrderDTO
{
    /**
     * @param  DeliveryOrderDetailDTO[]  $details
     * @param  DeliveryOrderItemDTO[]  $items
     */
    public function __construct(
        public readonly string $document_code,
        public readonly string $delivery_date,
        public readonly string $due_date,
        public readonly int $payment_term,
        public readonly ?string $description,
        public readonly bool $use_tax,
        public readonly int $company_id,
        public readonly int $shipping_id,
        public readonly ?int $sub_shipping_id,
        public readonly int $customer_id,
        public readonly ?string $customer_segment,
        public readonly array $details,
        public readonly array $items,
    ) {}

    /**
     * @param  string  $due_date  `delivery_date` + `customers.term_payment`,
     *                            dihitung server — frontend tidak mengirim
     *                            jatuh tempo, jadi termin pelanggan tidak bisa
     *                            disunting lewat request.
     * @param  string|null  $customer_segment  Segmen hasil pilihan user di
     *                                         form (divalidasi whitelist oleh
     *                                         controller); sumber pemilihan
     *                                         kolom harga `product_prices`.
     */
    public static function fromRequest(
        Request $request,
        int $paymentTerm,
        string $dueDate,
        ?string $customerSegment,
    ): self {
        $details = collect($request->transaction_details)
            ->map(fn ($detail) => new DeliveryOrderDetailDTO(
                name: (string) $detail['name'],
                type: (string) $detail['type'],
                value: $detail['value'],
            ))
            ->all();

        $items = collect($request->transaction_items)
            ->map(fn ($item) => new DeliveryOrderItemDTO(
                product_id: (int) $item['product_id'],
                quantity: (int) $item['quantity'],
                // Hanya dipakai kalau baris memakai harga manual; kalau tidak,
                // harga satuan diambil server dari `product_prices` dan angka
                // kiriman browser diabaikan.
                unit_price: (float) ($item['unit_price'] ?? 0),
                use_manual_price: (bool) ($item['use_manual_price'] ?? false),
                promo_product_id: ($item['promo_product_id'] ?? null) !== null
                    ? (int) $item['promo_product_id']
                    : null,
            ))
            ->all();

        return new self(
            document_code: (string) $request->document_code,
            delivery_date: substr((string) $request->delivery_date, 0, 10),
            due_date: $dueDate,
            payment_term: $paymentTerm,
            description: $request->description !== null && $request->description !== ''
                ? (string) $request->description
                : null,
            use_tax: self::extractFlag($details, 'USE_TAX', true),
            company_id: (int) $request->company_id,
            shipping_id: (int) $request->shipping_id,
            sub_shipping_id: $request->sub_shipping_id !== null
                ? (int) $request->sub_shipping_id
                : null,
            customer_id: (int) $request->customer_id,
            customer_segment: $customerSegment,
            details: $details,
            items: $items,
        );
    }

    /**
     * Boolean dokumen diambil dari `transaction_details` (satu sumber
     * kebenaran), sama seperti `CreatePurchaseOrderDTO::extractUseTax()`.
     */
    private static function extractFlag(array $details, string $type, bool $default): bool
    {
        $detail = collect($details)->first(fn ($detail) => $detail->type === $type);

        return $detail === null
            ? $default
            : filter_var($detail->value, FILTER_VALIDATE_BOOLEAN);
    }
}
