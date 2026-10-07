<?php

namespace App\Modules\Stock\Queries;

use Illuminate\Support\Facades\DB;

class GetGradualStockQuery
{
    /**
     * Barang TERTUNDA (gradually): item yang punya discrepancy type
     * GRADUALLY berstatus OPEN — qty yang akan dikirim menyusul, beserta
     * SSO asal penerimaannya.
     *
     * @param  array{search?: string, company_id?: int|null}  $filters
     */
    public function execute(array $filters)
    {
        $query = DB::table('receiving_discrepancies as rd')
            ->join('receiving_items as ri', 'ri.id', '=', 'rd.receiving_items_id')
            ->join('receivings as r', 'r.id', '=', 'ri.receiving_id')
            ->join('transaction_items as ti', 'ti.id', '=', 'ri.transaction_items_id')
            ->join('products as p', 'p.id', '=', 'ti.product_id')
            ->join('transactions as tx', 'tx.id', '=', 'r.transaction_id')
            ->where('rd.type', 'GRADUALLY')
            ->where('rd.status', 'OPEN')
            ->selectRaw("
                rd.id,
                rd.remaining_qty,
                rd.description,
                r.received_at,
                tx.transaction_code as sso_code,
                p.code as product_code,
                p.name as product_name,
                p.unit as product_unit,
                ti.id as transaction_items_id
            ");

        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.code', 'like', "%{$search}%")
                    ->orWhere('p.name', 'like', "%{$search}%")
                    ->orWhere('tx.transaction_code', 'like', "%{$search}%");
            });
        }

        $companyId = $filters['company_id'] ?? null;

        if ($companyId) {
            // Barang bertahap belum tentu punya journal IN — filter company
            // lewat journal penerimaan item yang sama bila ada.
            $query->whereExists(function ($q) use ($companyId) {
                $q->select(DB::raw(1))
                    ->from('product_journals as pj')
                    ->whereColumn('pj.transaction_items_id', 'ti.id')
                    ->where('pj.company_id', $companyId);
            });
        }

        return $query
            ->orderByDesc('r.received_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($row) => $this->mapRow($row));
    }

    private function mapRow(object $row): object
    {
        $row->id = (int) $row->id;
        $row->remaining_qty = (int) $row->remaining_qty;
        $row->transaction_items_id = (int) $row->transaction_items_id;

        return $row;
    }
}
