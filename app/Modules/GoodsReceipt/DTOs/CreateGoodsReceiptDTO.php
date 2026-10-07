<?php

namespace App\Modules\GoodsReceipt\DTOs;

class CreateGoodsReceiptDTO
{
    /**
     * @param  array<int, array{
     *     transaction_items_id: int,
     *     received_qty: int,
     *     splits: array<int, array{
     *         batch_code: string,
     *         quantity: int,
     *         expiry_date: ?string,
     *         stagnation_limit_date: ?string
     *     }>,
     *     discrepancies: array<int, array{
     *         type: string,
     *         remaining_qty: int,
     *         description: ?string
     *     }>
     * }>  $items
     */
    public function __construct(
        public readonly int $transaction_id,
        public readonly int $company_id,
        public readonly ?string $noted,
        public readonly array $items,
        public readonly int $received_by,
    ) {}

    public static function fromValidated(array $data, int $receivedBy): self
    {
        $items = array_map(fn (array $item) => [
            'transaction_items_id' => (int) $item['transaction_items_id'],
            'received_qty' => (int) $item['received_qty'],
            'splits' => array_map(fn (array $split) => [
                'batch_code' => trim($split['batch_code']),
                'quantity' => (int) $split['quantity'],
                'expiry_date' => $split['expiry_date'] ?: null,
                'stagnation_limit_date' => $split['stagnation_limit_date'] ?: null,
            ], $item['splits']),
            'discrepancies' => array_map(fn (array $discrepancy) => [
                'type' => $discrepancy['type'],
                'remaining_qty' => (int) $discrepancy['remaining_qty'],
                'description' => $discrepancy['description'] ?: null,
            ], $item['discrepancies'] ?? []),
        ], $data['items']);

        return new self(
            transaction_id: (int) $data['transaction_id'],
            company_id: (int) $data['company_id'],
            noted: $data['noted'] ?? null,
            items: $items,
            received_by: $receivedBy,
        );
    }
}
