<?php

namespace App\Modules\PurchaseOrder\DTOs;

class PurchaseOrderItemDTO
{
    /**
     * @param  float  $unit_price  Harga satuan BRUTO (sudah termasuk PPN).
     *                             `base_price` dan `total_price` dihitung dari
     *                             angka ini oleh `PurchaseOrderCalculator`.
     */
    public function __construct(
        public readonly int $product_id,
        public readonly int $quantity,
        public readonly float $unit_price,
        public readonly ?int $trade_promo_id,
    ) {}
}
