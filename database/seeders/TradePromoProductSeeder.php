<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pasangkan produk dengan trade promo.
 *
 * Versi lama memakai `for ($productId = 1; $productId <= 10)` plus
 * `shuffle()` + `rand()`, yang menyebabkan tiga masalah:
 *
 * 1. `product_id` 1-10 di-hardcode. Di database yang produknya pernah dihapus,
 *    id itu tidak ada dan `products_trade_promo_product_id_fk` menolak insert —
 *    `db:seed` gagal total. Sekarang id produk & promo diambil dari DB.
 * 2. `shuffle()` + `rand()` membuat hasil seeding berbeda tiap kali dijalankan.
 *    Pasangan produk–promo sekarang dipilih bergilir, deterministik.
 * 3. `insert()` tanpa unique index menyisakan baris duplikat tiap kali seeding
 *    diulang. Sekarang `insertOrIgnore()` + unique index
 *    `(trade_promo_id, product_id)` dari migration
 *    `2026_10_04_000002_add_unique_index_to_products_trade_promo_table`.
 *
 * Seeder ini melengkapi pasangan yang sudah diisi
 * `ProductTradePromoSeeder`.
 */
class TradePromoProductSeeder extends Seeder
{
    /**
     * Berapa promo yang dipasangkan ke tiap produk.
     */
    private const PROMOS_PER_PRODUCT = 3;

    public function run(): void
    {
        $productIds = DB::table('products')->orderBy('id')->pluck('id');
        // Hanya promo aktif — endpoint `api/product` memfilter `is_active = 1`,
        // jadi promo nonaktif tidak akan pernah muncul di form pemesanan.
        $promoIds = DB::table('trade_promo')
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($productIds->isEmpty() || $promoIds->isEmpty()) {
            $this->command?->warn(
                'Produk atau trade promo kosong — pivot tidak diisi. Jalankan ProductSeeder & TradePromoSeeder dulu.',
            );

            return;
        }

        $promoCount = $promoIds->count();
        $rows = [];
        $now = now();

        foreach ($productIds->values() as $index => $productId) {
            // Offset [0, 3, 7] memberi pasangan yang tersebar merata dan tetap
            // sama di setiap kali seeding.
            for ($slot = 0; $slot < self::PROMOS_PER_PRODUCT; $slot++) {
                $rows[] = [
                    'trade_promo_id' => $promoIds[($index + $slot * 3) % $promoCount],
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $inserted = DB::table('products_trade_promo')->insertOrIgnore($rows);

        $this->command?->info(sprintf(
            'TradePromoProduct: %d pasang baru, %d sudah ada sebelumnya.',
            $inserted,
            count($rows) - $inserted,
        ));
    }
}
