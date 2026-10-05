<?php

namespace App\Modules\SubSalesOrder\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class FindSubSalesOrderQuery
{
    public function execute(int $id): ?object
    {
        $transaction = DB::table('transactions as tx')
            ->leftJoin('users as creator', 'creator.id', '=', 'tx.created_by')
            ->where('tx.id', $id)
            ->where('tx.transaction_type', TransactionType::SubSalesOrder->value)
            ->first([
                'tx.id',
                'tx.transaction_code',
                'tx.correlation_id',
                'tx.description',
                'tx.created_at',
                'tx.created_by',
                'creator.name as created_by_name',
            ]);

        if ($transaction === null) {
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
            ->leftJoin('products as p', 'p.id', '=', 'ti.product_id')
            ->where('ti.transaction_id', $transaction->id)
            ->orderBy('ti.id')
            ->get([
                'ti.id',
                'ti.product_id',
                'ti.quantity',
                'ti.base_price',
                'ti.total_price',
                'ti.trade_promo_id',
                'p.code as product_code',
                'p.name as product_name',
                'p.unit as product_unit',
            ]);

        $purchaseOrderId = $transaction->details['id_purchase_order'] ?? null;
        $purchaseOrderQuery = DB::table('transactions')
            ->where('transaction_type', TransactionType::PurchaseOrder->value);

        $purchaseOrder = $purchaseOrderId
            ? $purchaseOrderQuery->where('id', $purchaseOrderId)->first([
                'id',
                'transaction_code',
            ])
            : DB::table('transactions')
                ->where('transaction_type', TransactionType::PurchaseOrder->value)
                ->where('correlation_id', $transaction->correlation_id)
                ->first(['id', 'transaction_code']);

        $transaction->purchase_order = $purchaseOrder;

        return $transaction;
    }
}
