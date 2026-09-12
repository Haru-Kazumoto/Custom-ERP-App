<?php

namespace App\Modules\SubSalesOrder\DTOs;

use Illuminate\Http\Request;

class CreateSubSalesOrderDTO
{
    public function __construct(
        private readonly string $no_bukti,
        private readonly int $purchase_order_id,
        private readonly string $correlation_id,
        private readonly ?string $description,
        private readonly array $items,
        private readonly array $details,
        private readonly int $created_by
    ) {}

    public function toArray(): array
    {
        return [
            'no_bukti'          => $this->no_bukti,
            'purchase_order_id' => $this->purchase_order_id,
            'correlation_id'    => $this->correlation_id,
            'description'       => $this->description,
            'items'             => $this->items,
            'created_by'        => $this->created_by,
            'details'           => $this->details
        ];
    }

    public static function fromRequest(Request $request)
    {
        return new self(
            no_bukti: $request->input('no_bukti'),
            purchase_order_id: $request->input('purchase_order_id'),
            correlation_id: $request->input('correlation_id'),
            description: $request->input('description'),
            items: $request->input('items', []),
            created_by: $request->user()->id,
            details: $request->input('details', [])
        );
    }
}