<?php

namespace App\Modules\DeliveryOrder\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat langkah approval satu dokumen DO — kebalikan dari
 * `GetPurchaseOrderApprovalsQuery` hanya pada filter tipenya.
 */
class GetDeliveryOrderApprovalsQuery
{
    public function execute(int $delivery_order_id)
    {
        return DB::table('transaction_approvals as ta')
            ->select(
                'ta.id',
                'ta.order',
                'ta.status',
                'ta.description',
                'r.name as role',
                'sr.name as sub_role',
                'ta.proceed_at',
                'u.name as proceed_by'
            )
            ->leftJoin('roles as r', 'r.id', '=', 'ta.role_id')
            ->leftJoin('sub_roles as sr', 'sr.id', '=', 'ta.sub_role_id')
            ->leftJoin('users as u', 'u.id', '=', 'ta.proceed_by')
            ->where('ta.transaction_id', $delivery_order_id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transactions as t')
                    ->whereColumn('t.id', 'ta.transaction_id')
                    ->where('t.transaction_type', TransactionType::DeliveryOrder->value);
            })
            ->get()
            ->toArray();
    }
}
