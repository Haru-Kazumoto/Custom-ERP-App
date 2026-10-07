<?php

namespace App\Modules\Stock\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Dasar aggregate stok dari `product_journals`: stok = SUM(IN) - SUM(OUT).
 *
 * Dipakai bersama oleh query daftar barang/batch — method di bawah hanya
 * menyusun SELECT + filter sehingga perhitungan stok tidak berbeda antar
 * halaman.
 */
abstract class StockQueryBase
{
    /**
     * @param  array{search?: string, company_id?: int|null}  $filters
     */
    protected function applyProductFilters($query, array $filters, string $productAlias = 'p', string $journalAlias = 'pj')
    {
        $search = trim($filters['search'] ?? '');

        if ($search !== '') {
            $query->where(function ($q) use ($productAlias, $search) {
                $q->where("{$productAlias}.code", 'like', "%{$search}%")
                    ->orWhere("{$productAlias}.name", 'like', "%{$search}%");
            });
        }

        $companyId = $filters['company_id'] ?? null;

        if ($companyId) {
            $query->where("{$journalAlias}.company_id", $companyId);
        }

        return $query;
    }

    protected function stockExpression(string $journalAlias = 'pj'): string
    {
        return "SUM(CASE WHEN {$journalAlias}.action = 'IN' THEN {$journalAlias}.quantity ELSE -{$journalAlias}.quantity END)";
    }
}
