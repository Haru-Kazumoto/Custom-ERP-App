<?php

namespace App\Modules\Finance\Repositories;

use App\Enum\TransactionType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class FinanceDashboardRepository
{
    /**
     * Angka ringkas untuk kartu statistik dashboard finance.
     *
     * Antrean approval memakai filter yang sama persis dengan
     * `GetPendingApprovalsQuery` supaya jumlah di kartu dan isi tabel tidak
     * pernah berbeda karena definisi "menunggu" yang tidak sama.
     */
    public function getSummary(): array
    {
        $pending = $this->pendingApprovalQuery();
        $pendingCount = (clone $pending)->count();
        $pendingValue = (float) ((clone $pending)->sum('tx.grand_total'));

        $byType = (clone $pending)
            ->select('tx.transaction_type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('tx.transaction_type')
            ->pluck('total', 'transaction_type')
            ->map(fn ($total) => (int) $total)
            ->all();

        return [
            'pending_approval' => [
                'count' => $pendingCount,
                'value' => $pendingValue,
                'by_type' => $byType,
            ],
            'invoice' => $this->invoiceSummary(),
            'promo_claim' => $this->promoClaimSummary(),
        ];
    }

    private function pendingApprovalQuery(): Builder
    {
        return DB::table('transaction_approvals as ta')
            ->join('transactions as tx', 'tx.id', '=', 'ta.transaction_id')
            ->join('roles as r', 'r.id', '=', 'ta.role_id')
            ->where('r.code', 'finance')
            ->where('ta.status', 'PENDING')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_approvals as prev')
                    ->whereColumn('prev.transaction_id', 'ta.transaction_id')
                    ->whereColumn('prev.order', '<', 'ta.order')
                    ->where('prev.status', 'PENDING');
            });
    }

    private function invoiceSummary(): array
    {
        $base = DB::table('transactions as tx')
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
            ->where('tx.transaction_type', TransactionType::Invoice->value);

        $row = (clone $base)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(tx.grand_total), 0) as total_value, COALESCE(SUM(ip.paid_amount), 0) as total_paid')
            ->first();

        $total = (float) ($row->total_value ?? 0);
        $paid = (float) ($row->total_paid ?? 0);

        return [
            'count' => (int) ($row->total_count ?? 0),
            'total' => $total,
            'paid' => $paid,
            'outstanding' => max(0, $total - $paid),
        ];
    }

    private function promoClaimSummary(): array
    {
        $row = DB::table('promo_claim')
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(grand_total), 0) as total_value')
            ->first();

        return [
            'count' => (int) ($row->total_count ?? 0),
            'total' => (float) ($row->total_value ?? 0),
        ];
    }
}
