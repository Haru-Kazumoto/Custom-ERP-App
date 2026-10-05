<?php

namespace App\Modules\Finance\Queries;

use Illuminate\Support\Facades\DB;

class GetPendingApprovalsQuery
{
    /**
     * Antrean approval untuk role finance, lintas semua tipe dokumen.
     *
     * Finance,Turut tampil satu baris per dokumen: hanya langkah PENDING milik
     * finance yang diambil, bukan seluruh rantai approval.
     *
     * Syarat "tidak ada langkah lebih awal yang masih PENDING" mencegah dokumen
     * yang langkah sebelumnya sudah disetujui ikut masuk antrean.
     *
     * Syarat "tidak ada langkah lebih awal yang berhenti" (NEED_REVISION, dan
     * REJECTED untuk dokumen lama) mencegah langkah finance yang jadi `order` 2
     * ikut masuk antrean setelah langkah 1 menolak. `generateApprovals()` membuat
     * semua langkah sekaligus dengan status PENDING, jadi tanpa filter ini
     * finance bisa memutuskan dokumen yang rantainya sudah berhenti di langkah
     * sebelumnya. `REJECTED` tidak lagi dibuat sejak alur PO memakai
     * `NEED_REVISION`, tapi tetap dicantumkan agar dokumen lama tidak bocor ke
     * antrean.
     *
     * Tipe dokumen tidak dibatasi. PO sudah lewat `generateApprovals()` (finance
     * sebagai `order` 1), sementara DO dan faktur otomatis ikut terisi begitu
     * modulnya dibuat tanpa perlu mengubah query ini.
     */
    public function execute(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));

        return DB::table('transaction_approvals as ta')
            ->join('transactions as tx', 'tx.id', '=', 'ta.transaction_id')
            ->join('roles as r', 'r.id', '=', 'ta.role_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'tx.created_by')
            ->where('r.code', 'finance')
            ->where('ta.status', 'PENDING')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_approvals as prev')
                    ->whereColumn('prev.transaction_id', 'ta.transaction_id')
                    ->whereColumn('prev.order', '<', 'ta.order')
                    ->where('prev.status', 'PENDING');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_approvals as stopped')
                    ->whereColumn('stopped.transaction_id', 'ta.transaction_id')
                    ->whereColumn('stopped.order', '<', 'ta.order')
                    ->whereIn('stopped.status', ['REJECTED', 'NEED_REVISION']);
            })
            ->orderBy('ta.order')
            ->orderByDesc('tx.created_at')
            ->limit($limit)
            ->get([
                'ta.id as approval_id',
                'ta.order as approval_order',
                'tx.id as transaction_id',
                'tx.transaction_code',
                'tx.transaction_type',
                'tx.grand_total',
                'tx.created_at',
                'creator.name as requested_by',
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
