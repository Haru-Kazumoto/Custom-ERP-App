<?php

namespace App\Modules\DeliveryOrder\DTOs;

class DeliveryOrderDetailDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly mixed $value,
    ) {}
}
