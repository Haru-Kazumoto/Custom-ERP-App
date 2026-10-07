<?php

namespace App\Modules\Stock\Queries;

use Illuminate\Support\Facades\DB;

class GetStagnantStockQuery extends StockQueryBase
{
    /**
     * Barang STAGNAN: batch yang masih punya stok tapi `stagnation_limit_date`
     * paling tidak sudah lewat dari hari ini. Diurutkan dari yang paling lama
     * melewati batas.
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
                ->havingRaw('MIN(pj.stagnation_limit_date) IS NOT NULL')
                ->havingRaw('MIN(pj.stagnation_limit_date) < CURDATE()')
                ->orderByRaw('MIN(pj.stagnation_limit_date) ASC')
                ->orderBy('p.code'),
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
