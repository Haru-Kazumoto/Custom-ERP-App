<?php

namespace App\Modules\Dashboard\Queries;

use Illuminate\Support\Facades\DB;

class GetWarehouseDashboardDataQuery
{
    public function execute(): array
    {
        $receivingItems = DB::table('receiving_items')
            ->select('receiving_id')
            ->selectRaw('SUM(received_qty) as received_units')
            ->selectRaw('COUNT(DISTINCT transaction_items_id) as item_count')
            ->groupBy('receiving_id');

        $deliveryOrderItems = DB::table('receivings as r')
            ->join('receiving_items as ri', 'ri.receiving_id', '=', 'r.id')
            ->select('r.transaction_id')
            ->selectRaw('COUNT(DISTINCT ri.transaction_items_id) as item_count')
            ->groupBy('r.transaction_id');

        $latestApproval = DB::table('transaction_approvals')
            ->select('transaction_id')
            ->selectRaw('MAX(`order`) as latest_order')
            ->groupBy('transaction_id');

        return [
            'summary' => [
                'productCount' => DB::table('products')->count(),
                'purchaseOrderCount' => DB::table('transactions')
                    ->where('transaction_type', 'PO')
                    ->count(),
                'receivedUnits' => (int) DB::table('receiving_items')->sum('received_qty'),
                'discrepancyCount' => DB::table('receiving_discrepancies')->count(),
            ],
            'deliveryOrders' => DB::table('transactions as tx')
                ->leftJoinSub($latestApproval, 'latest_approval', function ($join) {
                    $join->on('latest_approval.transaction_id', '=', 'tx.id');
                })
                ->leftJoin('transaction_approvals as ta', function ($join) {
                    $join->on('ta.transaction_id', '=', 'tx.id')
                        ->on('ta.order', '=', 'latest_approval.latest_order');
                })
                ->leftJoinSub($deliveryOrderItems, 'items', function ($join) {
                    $join->on('items.transaction_id', '=', 'tx.id');
                })
                ->whereIn('tx.transaction_type', ['DO', 'DELIVERY_ORDER'])
                ->orderByDesc('tx.created_at')
                ->limit(10)
                ->get([
                    'tx.id',
                    'tx.transaction_code as code',
                    'tx.created_at as date',
                    'ta.status',
                    'items.item_count as items_count',
                ])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'date' => $row->date,
                    'status' => $row->status ?: 'Belum diproses',
                    'itemsCount' => (int) ($row->items_count ?? 0),
                ])
                ->all(),
            'recentReceivings' => DB::table('receivings as r')
                ->join('transactions as tx', 'tx.id', '=', 'r.transaction_id')
                ->leftJoinSub($receivingItems, 'items', function ($join) {
                    $join->on('items.receiving_id', '=', 'r.id');
                })
                ->where('tx.transaction_type', 'PO')
                ->orderByDesc('r.received_at')
                ->limit(10)
                ->get([
                    'r.id',
                    'tx.transaction_code as purchaseOrderCode',
                    'r.received_at as date',
                    'r.status',
                    'items.item_count as itemsCount',
                    'items.received_units as receivedUnits',
                ])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'purchaseOrderCode' => $row->purchaseOrderCode,
                    'date' => $row->date,
                    'status' => $row->status,
                    'itemsCount' => (int) ($row->itemsCount ?? 0),
                    'receivedUnits' => (int) ($row->receivedUnits ?? 0),
                ])
                ->all(),
        ];
    }
}
