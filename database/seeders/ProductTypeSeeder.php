<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tema data: PT distributor bahan kue & tepung-tepungan.
     *
     * Catatan urutan: `ProductSeeder` mereferensikan `product_type_id` 1, 2, dan
     * 4 secara hardcode, jadi baris pada posisi tersebut TIDAK boleh diacak:
     *   1 = Bahan Kue        -> menampung terigu, tapioka, beras, gula, minyak, garam
     *   2 = Kemasan          -> menampung plastik kemasan & karung
     *   4 = Lakban & Perekat -> menampung lakban coklat
     * Baris lain bebas diurutkan ulang karena tidak direferensikan.
     *
     * `updateOrCreate` di kunci `code` supaya seeder idempoten: menjalankan ulang
     * memperbarui nama pada baris yang sama tanpa menggeser `id`.
     */
    public function run(): void
    {
        $types = [
            // Tiga posisi di bawah dikunci oleh ProductSeeder.
            ['name' => 'Bahan Kue', 'code' => 'PT001'],
            ['name' => 'Kemasan', 'code' => 'PT002'],
            ['name' => 'Minuman & Olahan Minuman', 'code' => 'PT003'],
            ['name' => 'Lakban & Perekat', 'code' => 'PT004'],

            ['name' => 'Tepung & Olahan Tepung', 'code' => 'PT005'],
            ['name' => 'Gula & Pemanis', 'code' => 'PT006'],
            ['name' => 'Susu & Olahan Susu', 'code' => 'PT007'],
            ['name' => 'Telur & Olahan Telur', 'code' => 'PT008'],
            ['name' => 'Margarine & Lemak', 'code' => 'PT009'],
            ['name' => 'Minyak Goreng', 'code' => 'PT010'],
            ['name' => 'Garam & Rempah', 'code' => 'PT011'],
            ['name' => 'Kakao & Cokelat', 'code' => 'PT012'],
            ['name' => 'Kental Manis & Sirup', 'code' => 'PT013'],
            ['name' => 'Baking Powder & Soda', 'code' => 'PT014'],
            ['name' => 'Ragi & Khamir', 'code' => 'PT015'],
            ['name' => 'Pewarna & Pengawet', 'code' => 'PT016'],
            ['name' => 'Kemasan Kertas & Karton', 'code' => 'PT017'],
            ['name' => 'Tali, Label & Stiker', 'code' => 'PT018'],
            ['name' => 'Alat & Perlengkapan Kue', 'code' => 'PT019'],
            ['name' => 'Produk Beku & Refrigerasi', 'code' => 'PT020'],
        ];

        foreach ($types as $type) {
            ProductType::updateOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name']],
            );
        }
    }
}
