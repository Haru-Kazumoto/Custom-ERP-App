<?php

namespace App\Modules\SubSalesOrder\Repositories;

use App\Enum\TransactionType;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionItem;

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
                'quantity' => $item->quantity,
                'base_price' => $item->base_price,
                'total_price' => $item->total_price,
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'trade_promo_id' => $item->trade_promo_id,
            ]);
        });

        collect($data['details'])->each(function ($detail) use ($transaction) {
            TransactionDetail::create([
                'name' => $detail['name'],
                'value' => (string) $detail['value'],
                'type' => $detail['type'],
                'transaction_id' => $transaction->id,
            ]);
        });

        return $transaction;
    }
}
