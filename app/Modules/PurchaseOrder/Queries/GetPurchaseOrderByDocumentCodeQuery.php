<?php

namespace App\Modules\PurchaseOrder\Queries;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class GetPurchaseOrderByDocumentCodeQuery
{
    public function __construct(protected GetPurchaseOrderItemsQuery $get_items)
    {}

    public function execute(string $transaction_code)
    {
        $transaction = DB::table('transactions as tx')
            ->selectRaw('
                tx.*,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        "name", td.name,
                        "value", td.value
                    )
                ) AS detail
            ')
            ->leftJoin('transaction_details as td', 'td.transaction_id', '=', 'tx.id')
            ->where('tx.transaction_code', '=', $transaction_code)
            ->groupBy([
                'tx.id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.correlation_id'
            ])
            ->first();

        $decode_detail = json_decode($transaction->detail, true);

        $transaction->detail = collect($decode_detail)
            ->mapWithKeys(function ($detail) {
                return [
                    strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value']
                ];
            });

        $transaction->items = $this->get_items->execute($transaction->id);

        return $transaction;
    }
}
