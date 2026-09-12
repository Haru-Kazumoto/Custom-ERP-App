<?php

namespace App\Modules\Product\Repositories;

use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function getAll(?string $search = null)
    {
        return DB::table('products as p')
            ->select([
                'p.id as product_id',
                'p.name',
                'p.code',
                'p.unit',
                'p.category',
                'p.price',
                'v.name as vendor',
                'pt.name as type',
                'pst.name as sub_type',
                'p.vendor_id',
                'p.product_type_id',
                'p.product_sub_type_id',
                // trade promos sebagai JSON array per produk
                DB::raw("(
                SELECT COALESCE(
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', tp.id,
                            'name', tp.name,
                            'price', tp.price,
                            'quota', tp.quota,
                            'is_active', tp.is_active
                        )
                    ), JSON_ARRAY()
                )
                FROM products_trade_promo as ptp
                JOIN trade_promo as tp ON tp.id = ptp.trade_promo_id
                WHERE ptp.product_id = p.id
                  AND tp.is_active = 1
            ) as trade_promos"),
            ])
            ->join('product_type as pt', 'pt.id', '=', 'p.product_type_id')
            ->join('product_sub_type as pst', 'pst.id', '=', 'p.product_sub_type_id')
            ->join('vendor as v', 'v.id', '=', 'p.vendor_id')
            ->when($search, function ($q, $search) {
                $q->where(function ($w) use ($search) {
                    $w->where('p.name', 'like', "%{$search}%")
                        ->orWhere('p.code', 'like', "%{$search}%");
                });
            })
            // ->limit(50) // batasi hasil untuk katalog ribuan
            ->get()
            ->map(function ($row) {
                // JSON_ARRAYAGG mengembalikan string di sebagian driver → decode
                $row->trade_promos = is_string($row->trade_promos)
                    ? json_decode($row->trade_promos, true)
                    : ($row->trade_promos ?? []);
                return $row;
            });
    }
}
