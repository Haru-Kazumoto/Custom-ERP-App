<?php

namespace App\Modules\PurchaseOrder\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GetPurchaseOrderApprovalsQuery
{
    public function execute(int $purchase_order_id)
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
            ->where('ta.transaction_id', $purchase_order_id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transactions as t')
                    ->whereColumn('t.id', 'ta.transaction_id')
                    ->where('t.transaction_type', TransactionType::PurchaseOrder->value);
            })
            ->get()
            ->toArray();
    }
}
