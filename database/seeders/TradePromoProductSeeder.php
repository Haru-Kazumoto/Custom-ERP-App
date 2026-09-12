<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TradePromoProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];

        for ($productId = 1; $productId <= 10; $productId++) {

            $promoIds = collect(range(1, 20))
                ->shuffle()
                ->take(rand(2, 5));

            foreach ($promoIds as $promoId) {
                $rows[] = [
                    'trade_promo_id' => $promoId,
                    'product_id' => $productId,
                ];
            }
        }

        DB::table('products_trade_promo')->insert($rows);
    }
}
