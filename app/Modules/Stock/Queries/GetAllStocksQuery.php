<?php

namespace App\Modules\Stock\Queries;

class GetAllStocksQuery extends StockQueryBase
{
    /**
     * Daftar semua barang tersedia di gudang — dikelompokkan PER PRODUK
     * dengan total stok semua batch-nya.
     *
     * @param  array{search?: string, company_id?: int|null}  $filters
     */
    public function execute(array $filters)
    {
        $query = $this->applyProductFilters(
            $this->queryBase(),
            $filters,
        );

        return $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($row) => $this->mapRow($row));
    }

    private function queryBase()
    {
        $stock = $this->stockExpression();

        return \Illuminate\Support\Facades\DB::table('product_journals as pj')
            ->join('products as p', 'p.id', '=', 'pj.product_id')
            ->selectRaw("
                p.id as product_id,
                p.code,
                p.name,
                p.unit,
                {$stock} as stock,
                COUNT(DISTINCT pj.batch_code) as batch_count
            ")
            ->groupBy('p.id', 'p.code', 'p.name', 'p.unit')
            ->havingRaw("{$stock} > 0")
            ->orderBy('p.code');
    }

    private function mapRow(object $row): object
    {
        $row->stock = (int) $row->stock;
        $row->batch_count = (int) $row->batch_count;

        return $row;
    }
}
