<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tema data: PT distributor bahan kue & tepung-tepungan. Kategori, satuan,
     * dan harga mengikuti bahan kue yang beredar di pasar Indonesia — harganya
     * per kg/pcs/pack, bukan harga bahan premium.
     *
     * Tiga hal penting di seeder ini:
     *
     * 1. `products.code` punya UNIQUE index, jadi `create()` di dalam loop akan
     *    Integrity violation pada seeding kedua. `updateOrCreate` keyed by `code`
     *    membuat seeder ini idempoten.
     * 2. FK `product_type_id` / `product_sub_type_id` dicocokkan dari `code`
     *    (PT005, PST002), bukan angka id. Id bisa bergeser kalau urutan seed
     *    diubah; kode tidak.
     * 3. Pembagian vendor round-robin (deterministik), bukan `rand()`, supaya
     *    hasil seeding tidak berubah tiap kali dijalankan.
     *
     * Prasyarat: ProductTypeSeeder, ProductSubTypeSeeder, dan VendorSeeder harus
     * dijalankan lebih dulu — sudah terdaftar di DatabaseSeeder sebelum class ini.
     */
    public function run(): void
    {
        $typeIds = DB::table('product_type')->pluck('id', 'code');
        $subTypeIds = DB::table('product_sub_type')->pluck('id', 'code');
        $vendorIds = DB::table('vendor')->orderBy('id')->pluck('id')->values();

        if ($typeIds->isEmpty() || $subTypeIds->isEmpty()) {
            $this->command?->warn(
                'product_type / product_sub_type kosong — produk disimpan tanpa tipe. Jalankan ProductTypeSeeder & ProductSubTypeSeeder lebih dulu.',
            );
        }

        $products = [
            // Terigu & tepung-tepungan
            ['name' => 'Tepung Terigu Protein Tinggi', 'code' => 'PRD001', 'unit' => 'KG', 'category' => 'TEPUNG', 'price' => 14500, 'type' => 'PT005', 'sub_type' => 'PST001'],
            ['name' => 'Tepung Terigu Protein Sedang', 'code' => 'PRD002', 'unit' => 'KG', 'category' => 'TEPUNG', 'price' => 13200, 'type' => 'PT005', 'sub_type' => 'PST002'],
            ['name' => 'Tepung Terigu Protein Rendah', 'code' => 'PRD011', 'unit' => 'KG', 'category' => 'TEPUNG', 'price' => 11800, 'type' => 'PT005', 'sub_type' => 'PST008'],
            ['name' => 'Tepung Tapioka', 'code' => 'PRD003', 'unit' => 'KG', 'category' => 'TEPUNG', 'price' => 9800, 'type' => 'PT005', 'sub_type' => 'PST002'],
            ['name' => 'Tepung Maizena', 'code' => 'PRD012', 'unit' => 'KG', 'category' => 'TEPUNG', 'price' => 12500, 'type' => 'PT005', 'sub_type' => 'PST002'],
            ['name' => 'Tepung Beras', 'code' => 'PRD004', 'unit' => 'KG', 'category' => 'TEPUNG', 'price' => 11000, 'type' => 'PT005', 'sub_type' => 'PST003'],

            // Gula, pemanis, lemak, garam & minyak
            ['name' => 'Gula Pasir', 'code' => 'PRD005', 'unit' => 'KG', 'category' => 'GULA', 'price' => 16500, 'type' => 'PT006', 'sub_type' => 'PST005'],
            ['name' => 'Gula Pasir Premium', 'code' => 'PRD013', 'unit' => 'KG', 'category' => 'GULA', 'price' => 19500, 'type' => 'PT006', 'sub_type' => 'PST001'],
            ['name' => 'Minyak Goreng', 'code' => 'PRD006', 'unit' => 'LTR', 'category' => 'MINYAK', 'price' => 20500, 'type' => 'PT010', 'sub_type' => 'PST005'],
            ['name' => 'Margarine', 'code' => 'PRD015', 'unit' => 'BKS', 'category' => 'LEMAK', 'price' => 16800, 'type' => 'PT009', 'sub_type' => 'PST005'],
            ['name' => 'Garam Halus', 'code' => 'PRD007', 'unit' => 'KG', 'category' => 'GARAM', 'price' => 7200, 'type' => 'PT011', 'sub_type' => 'PST003'],

            // Susu, telur, kakao & cokelat
            ['name' => 'Susu Bubuk Full Cream', 'code' => 'PRD016', 'unit' => 'PAK', 'category' => 'SUSU', 'price' => 32000, 'type' => 'PT007', 'sub_type' => 'PST001'],
            ['name' => 'Susu Kental Manis', 'code' => 'PRD017', 'unit' => 'PCS', 'category' => 'SUSU', 'price' => 18500, 'type' => 'PT013', 'sub_type' => 'PST002'],
            ['name' => 'Telur Ayam', 'code' => 'PRD018', 'unit' => 'KG', 'category' => 'TELUR', 'price' => 29000, 'type' => 'PT008', 'sub_type' => 'PST005'],
            ['name' => 'Cokelat Bubuk', 'code' => 'PRD019', 'unit' => 'BKS', 'category' => 'COKLAT', 'price' => 27500, 'type' => 'PT012', 'sub_type' => 'PST002'],

            // Aditif baking
            ['name' => 'Baking Powder', 'code' => 'PRD020', 'unit' => 'SACHET', 'category' => 'BAKING', 'price' => 12500, 'type' => 'PT014', 'sub_type' => 'PST002'],
            ['name' => 'Ragi Instant', 'code' => 'PRD014', 'unit' => 'SACHET', 'category' => 'RAGI', 'price' => 9800, 'type' => 'PT015', 'sub_type' => 'PST002'],

            // Kemasan & perekat
            ['name' => 'Plastik Kemasan 1 Kg', 'code' => 'PRD008', 'unit' => 'PCS', 'category' => 'KEMASAN', 'price' => 850, 'type' => 'PT002', 'sub_type' => 'PST002'],
            ['name' => 'Karung 25 Kg', 'code' => 'PRD009', 'unit' => 'PCS', 'category' => 'KEMASAN', 'price' => 4500, 'type' => 'PT002', 'sub_type' => 'PST002'],
            ['name' => 'Lakban Coklat', 'code' => 'PRD010', 'unit' => 'ROLL', 'category' => 'LAKBAN', 'price' => 12000, 'type' => 'PT004', 'sub_type' => 'PST005'],
        ];

        // Diurutkan per kode supaya `id` mengikuti urutan kode (PRD001 -> id 1).
        // Berguna karena `TradePromoProductSeeder` memakai `product_id` 1-10, dan
        // membuat hasil seeding mudah dibaca serta stabil.
        usort($products, fn (array $a, array $b): int => strcmp($a['code'], $b['code']));

        foreach ($products as $index => $product) {
            Product::updateOrCreate(
                ['code' => $product['code']],
                [
                    'name' => $product['name'],
                    'unit' => $product['unit'],
                    'category' => $product['category'],
                    'price' => $product['price'],
                    'product_type_id' => $typeIds[$product['type']] ?? null,
                    'product_sub_type_id' => $subTypeIds[$product['sub_type']] ?? null,
                    // Pembagian rata supaya tidak ada vendor yang kosong.
                    'vendor_id' => $vendorIds->isEmpty()
                        ? null
                        : $vendorIds[$index % $vendorIds->count()],
                ],
            );
        }
    }
}
