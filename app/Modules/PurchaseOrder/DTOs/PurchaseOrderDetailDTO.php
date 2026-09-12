<?php

namespace App\Modules\PurchaseOrder\DTOs;

class PurchaseOrderDetailDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly mixed $value,
        public readonly string $data_type,
    ) {}
}
