<?php

namespace App\Modules\Finance\Queries;

use Illuminate\Support\Facades\DB;

class GetApprovalHistoryQuery
{
    /**
     * Riwayat keputusan approval yang pernah diambil finance.
     *
     * Kebalikan dari `GetPendingApprovalsQuery`: baris yang sudah bukan PENDING,
     * jadi yang tercatat sudah pernah disetujui, ditolak, atau perlu revisi.
     */
    public function execute(int $limit = 25): array
    {
        $limit = max(1, min($limit, 200));

        return DB::table('transaction_approvals as ta')
            ->join('transactions as tx', 'tx.id', '=', 'ta.transaction_id')
            ->join('roles as r', 'r.id', '=', 'ta.role_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'ta.proceed_by')
            ->where('r.code', 'finance')
            ->where('ta.status', '!=', 'PENDING')
            ->orderByDesc('ta.proceed_at')
            ->orderByDesc('ta.id')
            ->limit($limit)
            ->get([
                'ta.id as approval_id',
                'ta.order as approval_order',
                'ta.status',
                'ta.description',
                'ta.proceed_at',
                'tx.id as transaction_id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.grand_total',
                'approver.name as proceed_by',
            ])
            ->map(function ($row) {
                $row->approval_id = (int) $row->approval_id;
                $row->transaction_id = (int) $row->transaction_id;
                $row->approval_order = (int) $row->approval_order;
                $row->grand_total = (float) $row->grand_total;

                return $row;
            })
            ->toArray();
    }
}
