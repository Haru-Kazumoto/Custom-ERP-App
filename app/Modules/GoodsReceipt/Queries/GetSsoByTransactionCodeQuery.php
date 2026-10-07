<?php

namespace App\Modules\GoodsReceipt\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GetSsoByTransactionCodeQuery
{
    /**
     * Detail satu SSO beserta items-nya untuk halaman entry goods receipt.
     *
     * Mengembalikan `null` kalau kode tidak ditemukan, bukan SSO, atau sudah
     * pernah dicatat penerimaannya (1 SSO = 1 entry).
     */
    public function execute(string $transaction_code): ?object
    {
        $transaction = DB::table('transactions')
            ->select(['id', 'transaction_code', 'transaction_type', 'description', 'created_at'])
            ->where('transaction_code', $transaction_code)
            ->first();

        if (
            $transaction === null
            || $transaction->transaction_type !== TransactionType::SubSalesOrder->value
        ) {
            return null;
        }

        $alreadyReceived = DB::table('receivings')
            ->where('transaction_id', $transaction->id)
            ->exists();

        if ($alreadyReceived) {
            return null;
        }

        $transaction->details = DB::table('transaction_details')
            ->where('transaction_id', $transaction->id)
            ->get(['name', 'value'])
            ->mapWithKeys(fn ($detail) => [
                strtolower(str_replace(' ', '_', $detail->name)) => $detail->value,
            ])
            ->all();

        $transaction->items = DB::table('transaction_items as ti')
            ->join('products as p', 'p.id', '=', 'ti.product_id')
            ->where('ti.transaction_id', $transaction->id)
            ->orderBy('ti.id')
            ->get([
                'ti.id',
                'ti.product_id',
                'ti.quantity',
                'p.code as product_code',
                'p.name as product_name',
                'p.unit as product_unit',
            ])
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
