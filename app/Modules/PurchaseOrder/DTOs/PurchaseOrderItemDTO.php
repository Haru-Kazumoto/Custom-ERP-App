<?php

namespace App\Modules\PurchaseOrder\DTOs;

class PurchaseOrderItemDTO
{
    public function __construct(
        public readonly int $product_id,
        public readonly string $unit,
        public readonly int $quantity,
        public readonly float $amount,
        public readonly bool $use_tax,
        public readonly ?int $trade_promo_id,
        public readonly float $total_price,
    ) {}
}
