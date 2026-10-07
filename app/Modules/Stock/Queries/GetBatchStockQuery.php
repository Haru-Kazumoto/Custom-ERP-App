<?php

namespace App\Modules\Stock\Queries;

use Illuminate\Support\Facades\DB;

class GetBatchStockQuery extends StockQueryBase
{
    /**
     * Daftar stok PER KODE BARANG (batch hasil pecahan) — satu baris per
     * pasangan produk + batch_code dengan qty stok masing-masing.
     *
     * @param  array{search?: string, company_id?: int|null}  $filters
     */
    public function execute(array $filters)
    {
        $stock = $this->stockExpression();

        $query = $this->applyProductFilters(
            DB::table('product_journals as pj')
                ->join('products as p', 'p.id', '=', 'pj.product_id')
                ->selectRaw("
                    p.id as product_id,
                    p.code,
                    p.name,
                    p.unit,
                    pj.batch_code,
                    {$stock} as stock,
                    MIN(pj.expiry_date) as expiry_date,
                    MIN(pj.stagnation_limit_date) as stagnation_limit_date,
                    MAX(pj.created_at) as last_received_at
                ")
                ->groupBy('p.id', 'p.code', 'p.name', 'p.unit', 'pj.batch_code')
                ->havingRaw("{$stock} > 0")
                ->orderBy('p.code')
                ->orderBy('pj.batch_code'),
            $filters,
        );

        return $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($row) => $this->mapRow($row));
    }

    protected function mapRow(object $row): object
    {
        $row->stock = (int) $row->stock;

        return $row;
    }
}
