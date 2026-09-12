<?php

namespace App\Modules\PurchaseOrder\DTOs;

class ApprovePurchaseOrderDTO
{
    public function __construct(
        public readonly int $purchase_order_id,
        public readonly string $status,
        public readonly ?string $description,
    ){}

    public function toArray()
    {
        return [
            'purchase_order_id' => $this->purchase_order_id,
            'status' => $this->status,
            'description' => $this->description
        ];
    }
}
