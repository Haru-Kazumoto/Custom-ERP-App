<?php

namespace App\Modules\Stock\DTOs;

class RecordGradualArrivalDTO
{
    public function __construct(
        public readonly int $discrepancy_id,
        public readonly string $batch_code,
        public readonly int $quantity,
        public readonly ?string $expiry_date,
        public readonly ?string $stagnation_limit_date,
    ) {}

    public static function fromValidated(int $discrepancyId, array $data): self
    {
        return new self(
            discrepancy_id: $discrepancyId,
            batch_code: trim($data['batch_code']),
            quantity: (int) $data['quantity'],
            expiry_date: $data['expiry_date'] ?: null,
            stagnation_limit_date: $data['stagnation_limit_date'] ?: null,
        );
    }
}
