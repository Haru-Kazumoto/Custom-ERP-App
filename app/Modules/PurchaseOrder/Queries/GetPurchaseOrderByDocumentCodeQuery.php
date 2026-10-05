<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class GetPurchaseOrderByDocumentCodeQuery
{
    public function __construct(protected GetPurchaseOrderItemsQuery $get_items)
    {}

    public function execute(string $transaction_code)
    {
        $transaction = DB::table('transactions')
            ->select(['id', 'transaction_code', 'transaction_type'])
            ->where('transaction_code', $transaction_code)
            ->first();

        if ($transaction === null) {
            return null;
        }

        $transaction->detail = DB::table('transaction_details')
            ->where('transaction_id', $transaction->id)
            ->whereIn('name', [
                'Pemasok',
                'Alokasi',
                'Tanggal PO',
                'Transportasi',
                'Jenis Pengiriman',
            ])
            ->get(['name', 'value'])
            ->mapWithKeys(fn ($detail) => [
                strtolower(str_replace(' ', '_', $detail->name)) => $detail->value,
            ]);

        $transaction->items = $this->get_items->execute($transaction->id)
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'product_unit' => $item->product_unit,
            ]);

        return $transaction;
    }
}
