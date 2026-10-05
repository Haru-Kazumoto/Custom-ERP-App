<?php

namespace App\Modules\Finance\Queries;

use App\Enum\TransactionType;
use Illuminate\Support\Facades\DB;

class GetInvoiceOverviewQuery
{
    /**
     * Faktur yang sudah terbit — dibaca saja, bukan dibuat dari modul ini.
     *
     * Pembuatan faktur tetap milik modul invoicing; halaman ini hanya
     * menunjukkan nomor, nominal, dan sisa tagihan.
     *
     * Total pembayaran diambil lewat subquery, bukan `join` + `groupBy`, supaya
     * tidak bergantung pada relaxing `ONLY_FULL_GROUP_BY`. Asumsinya setiap baris
     * `invoice_payments` adalah pembayaran yang sudah berlaku; kalau modul
     * pembayaran nanti menambah baris pembatalan, penyaringannya dilakukan di
     * subquery ini.
     */
    public function execute(int $limit = 25): array
    {
        $limit = max(1, min($limit, 200));

        return DB::table('transactions as tx')
            ->leftJoinSub(
                DB::table('invoice_payments')
                    ->select('transaction_id')
                    ->selectRaw('SUM(paid_amount) as paid_amount')
                    ->groupBy('transaction_id'),
                'ip',
                'ip.transaction_id',
                '=',
                'tx.id'
            )
            ->where('tx.transaction_type', TransactionType::Invoice->value)
            ->orderByDesc('tx.created_at')
            ->limit($limit)
            ->get([
                'tx.id as transaction_id',
                'tx.transaction_code',
                'tx.created_at',
                'tx.due_date',
                'tx.sub_total',
                'tx.tax_amount',
                'tx.grand_total',
                'ip.paid_amount',
            ])
            ->map(function ($row) {
                $row->transaction_id = (int) $row->transaction_id;
                $row->sub_total = (float) $row->sub_total;
                $row->tax_amount = (float) $row->tax_amount;
                $row->grand_total = (float) $row->grand_total;
                $row->paid_amount = (float) ($row->paid_amount ?? 0);
                $row->outstanding_amount = max(0, $row->grand_total - $row->paid_amount);

                return $row;
            })
            ->toArray();
    }
}
