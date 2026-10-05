<?php

namespace Database\Seeders;

use App\Models\ProductSubType;
use Illuminate\Database\Seeder;

class ProductSubTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tema data: PT distributor bahan kue & tepung-tepungan.
     *
     * `product_sub_type` tidak punya FK ke `product_type` (lihat migrasi
     * `create_product_sub_type_table`), jadi sub-tipe adalah atribut silang —
     * bisa dipasangkan dengan tipe apa pun. Karena itu isinya berisi grade,
     * kadar protein, dan klaim produk, bukan "anak" dari tipe tertentu.
     *
     * Catatan urutan: `ProductSeeder` mereferensikan `product_sub_type_id` 1-5
     * secara hardcode, jadi lima baris pertama dipertahankan posisinya. Semuanya
     * sudah sesuai tema (tingkat kualitas untuk terigu), jadi isinya tidak
     * diubah — hanya 15 baris setelahnya yang diganti.
     *
     * `updateOrCreate` di kunci `code` supaya seeder idempoten.
     */
    public function run(): void
    {
        $subTypes = [
            // Lima posisi di bawah dikunci ProductSeeder.
            ['name' => 'Premium', 'code' => 'PST001'],
            ['name' => 'Standard', 'code' => 'PST002'],
            ['name' => 'Economy', 'code' => 'PST003'],
            ['name' => 'Imported', 'code' => 'PST004'],
            ['name' => 'Local', 'code' => 'PST005'],

            ['name' => 'Protein Tinggi', 'code' => 'PST006'],
            ['name' => 'Protein Sedang', 'code' => 'PST007'],
            ['name' => 'Protein Rendah', 'code' => 'PST008'],
            ['name' => 'Grade A', 'code' => 'PST009'],
            ['name' => 'Grade B', 'code' => 'PST010'],
            ['name' => 'Grade C', 'code' => 'PST011'],
            ['name' => 'Halal', 'code' => 'PST012'],
            ['name' => 'Tanpa Pengawet', 'code' => 'PST013'],
            ['name' => 'Rendah Lemak', 'code' => 'PST014'],
            ['name' => 'Tanpa Gula', 'code' => 'PST015'],
            ['name' => 'Organik', 'code' => 'PST016'],
            ['name' => 'Vegan', 'code' => 'PST017'],
            ['name' => 'Bulk', 'code' => 'PST018'],
            ['name' => 'Retail', 'code' => 'PST019'],
            ['name' => 'Repack', 'code' => 'PST020'],
        ];

        foreach ($subTypes as $subType) {
            ProductSubType::updateOrCreate(
                ['code' => $subType['code']],
                ['name' => $subType['name']],
            );
        }
    }
}
