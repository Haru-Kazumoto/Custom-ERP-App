<?php

namespace App\Modules\SubSalesOrder\Repositories;

use App\Enum\TransactionType;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

class SubSalesOrderRepository
{
    public function create(array $data)
    {
        $transaction = Transaction::create([
            'transaction_code' => $data['no_bukti'],
            'transaction_type' => TransactionType::SubSalesOrder->value,
            'correlation_id' => $data['correlation_id'],
            'created_by' => $data['created_by'],
            'description' => $data['description'] ?? null
        ]);

        collect($data['items'])->each(function ($item) use ($transaction) {
            TransactionItem::create([
                'quantity' => $item['quantity'],
                'transaction_id' => $transaction->id,
                'product_id' => $item['product_id'],
            ]);
        });

        collect($data['details'])->each(function ($detail) use ($transaction) {
            TransactionDetail::create([
                'name' => $detail['name'],
                'value' => $detail['value'],
                'type' => $detail['type'],
                'transaction_id' => $transaction->id,
            ]);
        });

        return $transaction;
    }

    public function retrieveDataFromPurchaseOrder(int $purchase_order_id)
    {
        return DB::table('transactions as tx')
            ->selectRaw("
                tx.id,
                tx.transaction_code,
                tx.transaction_type,
                tx.correlation_id,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'name', td.name,
                        'value', td.value
                    )
                ) AS detail
            ")
            ->leftJoin('transaction_detail as td', 'td.transaction_id', '=', 'tx.id')
            ->where('tx.id', '=', $purchase_order_id)
            ->groupBy([
                'tx.id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.correlation_id'
            ])
            ->first();
    }
}
