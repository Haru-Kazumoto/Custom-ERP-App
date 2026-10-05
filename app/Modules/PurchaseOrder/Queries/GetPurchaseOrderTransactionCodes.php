<?php

namespace App\Modules\PurchaseOrder\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GetPurchaseOrderTransactionCodes
{
    public function execute()
    {
        return DB::table('transactions as tx')
            ->select(['tx.id', 'tx.transaction_code'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_approvals as ta')
                    ->where('ta.transaction_id', '=', DB::raw('tx.id'))
                    // `REJECTED` sudah tidak dibuat lagi sejak alur PO memakai
                    // `NEED_REVISION` (lihat migration
                    // `2026_10_05_030000_...`). Nilainya tetap dicantumkan
                    // supaya dokumen lama yang masih punya langkah REJECTED
                    // tidak ikut dipakai ulang nomornya.
                    ->whereIn('ta.status', ['PENDING', 'NEED_REVISION', 'REJECTED']);
            })
            ->where('tx.transaction_type', '=', TransactionType::PurchaseOrder->value)
            ->orderByDesc('tx.created_at')
            ->get();
    }
}
