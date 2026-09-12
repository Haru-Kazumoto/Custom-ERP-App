<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class FindPurchaseOrderQuery
{
    public function __construct(private GetPurchaseOrderItemsQuery $get_order_items) {}

    public function execute(int $id, bool $with_approvals = false)
    {
        $transaction = DB::table('transactions as tx')
            ->select(
                'tx.id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.correlation_id',
                'tx.due_date',
                'tx.file_attachment',
                'tx.description',
                'tx.sub_total',
                'tx.total_discount',
                'tx.tax_amount',
                'tx.grand_total',
                DB::raw("
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'name', td.name,
                            'value', td.value,
                            'type', td.type
                        )
                    ) AS details
                ")
            )
            ->join('transaction_details as td', 'td.transaction_id', '=', 'tx.id')
            ->where('tx.id', $id)
            ->groupBy(
                'tx.id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.correlation_id',
                'tx.due_date',
                'tx.file_attachment',
                'tx.description',
                'tx.sub_total',
                'tx.total_discount',
                'tx.tax_amount',
                'tx.grand_total'
            )
            ->first();

        $decode_details = json_decode($transaction->details, true);

        $transaction->details = collect($decode_details)
            ->mapWithKeys(function ($detail) {
                return [
                    strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value']
                ];
            });

        $transaction->items = $this->get_order_items->execute($id);

        return $transaction;
    }
}
