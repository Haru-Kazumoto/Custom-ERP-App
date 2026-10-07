<?php

namespace App\Modules\Dashboard\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GetSalesDashboardDataQuery
{
    public function execute(int $userId): array
    {
        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();
        $nextMonth = $monthStart->addMonth();

        $salesOrders = DB::table('transactions as tx')
            ->where('tx.transaction_type', 'SSO')
            ->where('tx.created_by', $userId)
            ->where('tx.created_at', '>=', $monthStart)
            ->where('tx.created_at', '<', $nextMonth);

        $deliveryOrders = DB::table('transactions as tx')
            ->whereIn('tx.transaction_type', ['DO', 'DELIVERY_ORDER'])
            ->where('tx.created_by', $userId);

        $target = DB::table('employee_target')
            ->where('user_id', $userId)
            ->where('period', $now->year)
            ->get(['month', 'monthly'])
            ->first(fn (object $row) => $this->monthNumber($row->month) === $now->month);

        $targetAmount = (int) ($target->monthly ?? 0);
        $achievedAmount = (float) DB::table('transaction_items as ti')
            ->join('transactions as tx', 'tx.id', '=', 'ti.transaction_id')
            ->where('tx.transaction_type', 'SSO')
            ->where('tx.created_by', $userId)
            ->where('tx.created_at', '>=', $monthStart)
            ->where('tx.created_at', '<', $nextMonth)
            ->sum('ti.total_price');

        $latestApproval = DB::table('transaction_approvals')
            ->select('transaction_id')
            ->selectRaw('MAX(`order`) as latest_order')
            ->groupBy('transaction_id');

        $salesOrderItems = DB::table('transaction_items as ti')
            ->join('transactions as tx', 'tx.id', '=', 'ti.transaction_id')
            ->where('tx.transaction_type', 'SSO')
            ->where('tx.created_by', $userId)
            ->where('tx.created_at', '>=', $monthStart)
            ->where('tx.created_at', '<', $nextMonth);

        return [
            'summary' => [
                'deliveryOrdersThisMonth' => (clone $deliveryOrders)
                    ->where('tx.created_at', '>=', $monthStart)
                    ->where('tx.created_at', '<', $nextMonth)
                    ->count(),
                'deliveryOrdersNeedRevision' => (clone $deliveryOrders)
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('transaction_approvals as revision')
                            ->whereColumn('revision.transaction_id', 'tx.id')
                            ->where('revision.status', 'NEED_REVISION');
                    })
                    ->count(),
                'monthlyTarget' => $targetAmount,
                'salesAchieved' => $achievedAmount,
                'salesOrderCount' => (clone $salesOrders)->count(),
                'salesOrderUnits' => (clone $salesOrderItems)->sum('ti.quantity'),
                'targetIsSet' => $target !== null,
            ],
            'recentDeliveryOrders' => (clone $deliveryOrders)
                ->leftJoinSub($latestApproval, 'latest_approval', function ($join) {
                    $join->on('latest_approval.transaction_id', '=', 'tx.id');
                })
                ->leftJoin('transaction_approvals as ta', function ($join) {
                    $join->on('ta.transaction_id', '=', 'tx.id')
                        ->on('ta.order', '=', 'latest_approval.latest_order');
                })
                ->orderByDesc('tx.created_at')
                ->limit(8)
                ->get([
                    'tx.id',
                    'tx.transaction_code as code',
                    'tx.created_at as date',
                    'tx.grand_total as total',
                    'ta.status',
                ])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'date' => $row->date,
                    'total' => (float) $row->total,
                    'status' => $row->status ?: 'TERBIT',
                ])
                ->all(),
            'recentSalesOrders' => (clone $salesOrders)
                ->leftJoin('transaction_items as ti', 'ti.transaction_id', '=', 'tx.id')
                ->select('tx.id', 'tx.transaction_code as code', 'tx.created_at as date')
                ->selectRaw('COUNT(ti.id) as line_count')
                ->selectRaw('COALESCE(SUM(ti.total_price), 0) as total')
                ->groupBy('tx.id', 'tx.transaction_code', 'tx.created_at')
                ->orderByDesc('tx.created_at')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'date' => $row->date,
                    'lineCount' => (int) $row->line_count,
                    'total' => (float) $row->total,
                ])
                ->all(),
            'topProducts' => (clone $salesOrderItems)
                ->join('products as p', 'p.id', '=', 'ti.product_id')
                ->select('p.id', 'p.name', 'p.code', 'p.unit')
                ->selectRaw('SUM(ti.quantity) as quantity')
                ->selectRaw('SUM(ti.total_price) as total')
                ->groupBy('p.id', 'p.name', 'p.code', 'p.unit')
                ->orderByDesc('quantity')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'code' => $row->code,
                    'unit' => $row->unit,
                    'quantity' => (int) $row->quantity,
                    'total' => (float) $row->total,
                ])
                ->all(),
        ];
    }

    private function monthNumber(string $month): ?int
    {
        $month = mb_strtolower(trim($month));

        if (is_numeric($month)) {
            $number = (int) $month;

            return $number >= 1 && $number <= 12 ? $number : null;
        }

        $months = [
            1 => ['january', 'jan', 'januari'],
            2 => ['february', 'feb', 'februari'],
            3 => ['march', 'mar', 'maret'],
            4 => ['april', 'apr'],
            5 => ['may', 'mei'],
            6 => ['june', 'jun', 'juni'],
            7 => ['july', 'jul', 'juli'],
            8 => ['august', 'aug', 'agustus'],
            9 => ['september', 'sep'],
            10 => ['october', 'oct', 'oktober', 'okt'],
            11 => ['november', 'nov'],
            12 => ['december', 'dec', 'desember', 'des'],
        ];

        foreach ($months as $number => $names) {
            if (in_array($month, $names, true)) {
                return $number;
            }
        }

        return null;
    }
}
