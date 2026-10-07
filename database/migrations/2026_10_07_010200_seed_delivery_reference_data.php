<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data referensi wajib untuk modul Delivery Order: jenis pengiriman,
     * sub-pengiriman placeholder, dan contoh pelanggan per segmen.
     *
     * Semua tabel masih kosong, jadi seed ini memakai ID tetap supaya
     * migration berikutnya (`seed_demo_product_prices`) bisa mereferensikan
     * shipping/sub tanpa mencari ulang. Kalau tabel sudah terisi (mis. data
     * production), seed dilewati — data existing tidak boleh disentuh.
     */
    public function up(): void
    {
        if (DB::table('shippings')->exists()) {
            return;
        }

        $now = now();

        $shippings = [
            // id, name, code, has_sub
            [1, 'DEPO', 'DEPO', true],
            [2, 'DIRECT', 'DIRECT', true],
            [3, 'DIRECT DEPO', 'DIRECT_DEPO', true],
            [4, 'Delivery Order', 'DO', false],
        ];

        foreach ($shippings as [$id, $name, $code, $hasSub]) {
            DB::table('shippings')->insert([
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'has_sub' => $hasSub,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Sub placeholder untuk semua jenis KECUALI DO (DO tidak punya sub).
        $subShippings = [
            [1, 'Standar DEPO', 'DEPO-STD', 1],
            [2, 'Standar DIRECT', 'DIRECT-STD', 2],
            [3, 'Standar DIRECT DEPO', 'DIRECT_DEPO-STD', 3],
        ];

        foreach ($subShippings as [$id, $name, $code, $shippingId]) {
            DB::table('sub_shippings')->insert([
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'shipping_id' => $shippingId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Pelanggan contoh: hanya `name` yang wajib; `segment` menentukan
        // kolom harga mana di `product_prices` yang dipakai saat submit DO.
        $customers = [
            ['Toko Berkah Jaya', 'RETAIL'],
            ['Toko Sumber Rejeki', 'RETAIL'],
            ['Toko Maju Mundur', 'RETAIL'],
            ['CV Grosir Sentosa', 'WHOLESALE'],
            ['CV Berkah Grosir', 'WHOLESALE'],
            ['UD Sumber Pangan', 'WHOLESALE'],
            ['Ibu Siti Aminah', 'END_USER'],
            ['Budi Santoso', 'END_USER'],
            ['Koperasi Sejahtera', 'ALL_SEGMENT'],
            ['PT Mitra Dagang', 'ALL_SEGMENT'],
        ];

        foreach ($customers as $index => [$name, $segment]) {
            DB::table('customers')->insert([
                'name' => $name,
                'segment' => $segment,
                'term_payment' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Data referensi tidak dihapus saat rollback: migration berikutnya
        // (product_prices) bergantung padanya dan rollback satu langkah saja
        // akan meninggalkan baris harga yatim.
    }
};
