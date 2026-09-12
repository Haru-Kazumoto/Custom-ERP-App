<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class GetPurchaseOrderItemsQuery
{
    public function __construct() {}

    public function execute(int $purchase_order_id)
    {
        return DB::table('transaction_items as ti')
            ->select(
                'ti.id',
                'ti.base_price',
                'ti.total_price',
                'ti.quantity',
                'ti.product_id',
                'p.name as product_name',
                'p.unit as product_unit',
                'p.category as product_category',
                'p.code as product_code',
                'tp.name as trade_promo',
                'tp.price as trade_promo_price'
            )
            ->leftJoin('products as p', 'p.id', '=', 'ti.product_id')
            ->leftJoin('trade_promo as tp', 'tp.id', '=', 'ti.trade_promo_id')
            ->where('ti.transaction_id', $purchase_order_id)
            ->get();
    }
}
