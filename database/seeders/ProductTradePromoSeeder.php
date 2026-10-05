<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pasangkan produk dengan trade promo.
 *
 * Tabel `products_trade_promo` tidak punya FK ke produk maupun promo pada
 * seed awal, jadi `trade_promos` di respons `api/product` selalu `[]` dan
 * dropdown promo di form Purchase Order tidak pernah muncul.
 *
 * Idempoten lewat `insertOrIgnore()` yang ditopang unique index
 * `(trade_promo_id, product_id)` dari migration
 * `2026_10_04_000002_add_unique_index_to_products_trade_promo_table`.
 *
 * Prasyarat: ProductSeeder, ProductTradePromoSeeder, dan TradePromoSeeder
 * sudah dijalankan.
 */
class ProductTradePromoSeeder extends Seeder
{
    public function run(): void
    {
        $products = DB::table('products')->pluck('id', 'code');
        $promos = DB::table('trade_promo')
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('id');

        if ($products->isEmpty() || $promos->isEmpty()) {
            $this->command?->warn(
                'Produk atau trade promo kosong — pivot tidak diisi. Jalankan ProductSeeder & TradePromoSeeder dulu.',
            );

            return;
        }

        // Hanya sebagian produk yang dipromokan: promo umumnya berlaku untuk
        // barang tertentu, bukan untuk seluruh katalog.
        $pinnedProductCodes = [
            'PRD001', // Tepung Terigu Protein Tinggi
            'PRD002', // Tepung Terigu Protein Sedang
            'PRD005', // Gula Pasir
            'PRD012', // Tepung Maizena
            'PRD016', // Susu Bubuk Full Cream
        ];

        $rows = [];
        $now = now();

        foreach ($pinnedProductCodes as $index => $code) {
            $productId = $products[$code] ?? null;
            if ($productId === null) {
                continue;
            }

            // Satu promo untuk satu produk, dipilih bergilir dari daftar promo
            // aktif. Hasilnya deterministik dan tidak bentrok karena satu
            // produk hanya mendapat satu promo.
            $promoId = $promos[$index % $promos->count()];

            $rows[] = [
                'trade_promo_id' => $promoId,
                'product_id' => $productId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        $inserted = DB::table('products_trade_promo')->insertOrIgnore($rows);

        $this->command?->info(sprintf(
            'Pivot products_trade_promo: %d pasang baru, %d sudah ada sebelumnya.',
            $inserted,
            count($rows) - $inserted,
        ));
    }
}
