<?php

namespace App\Modules\PurchaseOrder\DTOs;

use Illuminate\Http\Request;

class CreatePurchaseOrderDTO
{
    /**
     * @param PurchaseOrderDetailDTO[] $details
     * @param PurchaseOrderItemDTO[] $items
     */
    public function __construct(
        public readonly string $document_code,
        public readonly int $term_of_payment,
        public readonly string $due_date,
        public readonly ?string $description,
        public readonly float $sub_total,
        public readonly float $tax_amount,
        public readonly float $total,
        public readonly array $details,
        public readonly array $items,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            document_code: $request->document_code,
            term_of_payment: $request->term_of_payment,
            due_date: $request->due_date,
            description: $request->description,
            sub_total: $request->sub_total,
            tax_amount: $request->tax_amount,
            total: $request->total,

            details: collect($request->transaction_details)
                ->map(function ($detail) {
                    return new PurchaseOrderDetailDTO(
                        name: $detail['name'],
                        type: $detail['type'],
                        value: $detail['value'],
                        data_type: $detail['data_type'],
                    );
                })
                ->all(),

            items: collect($request->transaction_items)
                ->map(fn($item) => new PurchaseOrderItemDTO(
                    product_id: $item['product_id'],
                    unit: $item['unit'],
                    quantity: $item['quantity'],
                    amount: $item['amount'],
                    use_tax: $item['use_tax'],
                    trade_promo_id: $item['trade_promo_id'],
                    total_price: $item['total_price'],
                ))
                ->all(),
        );
    }
}
