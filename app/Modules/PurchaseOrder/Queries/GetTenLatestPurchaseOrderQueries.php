<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class GetTenLatestPurchaseOrderQueries
{
    public function execute()
    {
        return DB::table('transactions as tx')
            ->leftJoin('transaction_details as td', 'td.transaction_id', '=', 'tx.id')
            ->select([
                'tx.transaction_code',
                'tx.created_at',
                'tx.grand_total',
            ])
            ->selectRaw("
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'name', td.name,
                        'value', td.value,
                        'type', td.type
                    )
                ) AS details
            ")
            ->where('tx.transaction_type', 'PO')
            ->groupBy('tx.id', 'tx.created_at')
            ->orderByDesc('tx.created_at')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                $details = json_decode($transaction->details, true);

                $transaction->detail = collect($details ?: [])
                    ->filter(fn($detail) => is_array($detail) && isset($detail['name']))
                    ->mapWithKeys(function ($detail) {
                        return [
                            strtolower(str_replace(' ', '_', $detail['name'])) => $detail['value'],
                        ];
                    })
                    ->toArray();

                unset($transaction->details);

                return $transaction;
            })
            ->toArray();
    }
}
