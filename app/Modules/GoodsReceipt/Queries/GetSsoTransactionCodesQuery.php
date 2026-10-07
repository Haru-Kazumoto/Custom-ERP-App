<?php

namespace App\Modules\GoodsReceipt\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GetSsoTransactionCodesQuery
{
    /**
     * Daftar SSO yang boleh dientry di warehouse: semua SSO yang sudah ada
     * di database (dianggap terbit dari PO) kecuali yang sudah pernah
     * dicatat penerimaannya — 1 SSO hanya boleh 1 kali entry.
     */
    public function execute()
    {
        return DB::table('transactions as tx')
            ->where('tx.transaction_type', TransactionType::SubSalesOrder->value)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('receivings as r')
                    ->whereColumn('r.transaction_id', 'tx.id');
            })
            ->orderByDesc('tx.created_at')
            ->get(['tx.id', 'tx.transaction_code']);
    }
}
