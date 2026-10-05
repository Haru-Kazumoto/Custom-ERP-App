<?php

namespace App\Modules\SubSalesOrder\DTOs;

class CreateSubSalesOrderDTO
{
    /**
     * @param  int[]  $purchase_order_item_ids
     */
    public function __construct(
        public readonly string $no_bukti,
        public readonly int $purchase_order_id,
        public readonly string $no_so,
        public readonly string $tanggal_kirim,
        public readonly ?string $description,
        public readonly array $purchase_order_item_ids,
        public readonly int $created_by,
    ) {}

    public static function fromValidated(array $data, int $createdBy): self
    {
        return new self(
            no_bukti: $data['no_bukti'],
            purchase_order_id: (int) $data['purchase_order_id'],
            no_so: $data['no_so'],
            tanggal_kirim: $data['tanggal_kirim'],
            description: $data['description'] ?? null,
            purchase_order_item_ids: array_map(
                'intval',
                array_column($data['items'], 'id'),
            ),
            created_by: $createdBy,
        );
    }
}
