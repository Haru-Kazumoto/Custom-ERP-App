<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Harga jual contoh untuk seluruh katalog (21 produk) × 4 kombinasi
     * pengiriman: DEPO, DIRECT, DIRECT_DEPO (masing-masing dengan sub
     * placeholder) dan DO (tanpa sub).
     *
     * Rumus sederhana supaya angka mudah diverifikasi saat smoke test:
     *   all_segment = harga katalog (`products.price`)
     *   wholesale   = all_segment + 5%
     *   retail      = all_segment + 10%
     *   end_user    = all_segment + 15%
     *
     * Kolom biaya lain (transportation_cost, overhead, dst.) diisi 0 —
     * perhitungannya milik modul PRICING.md yang di luar scope Delivery Order.
     */
    public function up(): void
    {
        if (DB::table('product_prices')->exists()) {
            return;
        }

        $products = DB::table('products')
            ->orderBy('id')
            ->get(['id', 'price']);

        if ($products->isEmpty()) {
            return;
        }

        $now = now();

        // [shipping_id, sub_shipping_id] — DO memakai sub NULL.
        $combos = [
            [1, 1],
            [2, 2],
            [3, 3],
            [4, null],
        ];

        $rows = [];

        foreach ($products as $product) {
            $base = (float) $product->price;

            $prices = [
                'all_segment_price' => $base,
                'wholesale_price' => $this->markup($base, 0.05),
                'retail_price' => $this->markup($base, 0.10),
                'end_user_price' => $this->markup($base, 0.15),
            ];

            foreach ($combos as [$shippingId, $subShippingId]) {
                $rows[] = array_merge([
                    'product_id' => $product->id,
                    'shipping_id' => $shippingId,
                    'sub_shipping_id' => $subShippingId,
                    'base_price' => $base,
                    'base_percentage' => 0,
                    'transportation_cost' => 0,
                    'overhead_cost' => 0,
                    'marketing_budget_cost' => 0,
                    'bad_debt_cost' => 0,
                    'saving_cost' => 0,
                    'rounded_price' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $prices);
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('product_prices')->insert($chunk);
        }
    }

    private function markup(float $base, float $percent): float
    {
        return round($base * (1 + $percent), 2);
    }

    public function down(): void
    {
        // Data contoh tidak dihapus saat rollback supaya tidak ada form yang
        // kehilangan harga di tengah pengujian.
    }
};
