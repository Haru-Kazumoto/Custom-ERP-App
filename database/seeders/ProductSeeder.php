<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Tepung Terigu Protein Tinggi',
                'code' => 'PRD001',
                'unit' => 'KG',
                'category' => 'TEPUNG',
                'price' => 14500,
                'product_type_id' => 1,
                'product_sub_type_id' => 1,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Tepung Terigu Protein Sedang',
                'code' => 'PRD002',
                'unit' => 'KG',
                'category' => 'TEPUNG',
                'price' => 13200,
                'product_type_id' => 1,
                'product_sub_type_id' => 2,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Tepung Tapioka',
                'code' => 'PRD003',
                'unit' => 'KG',
                'category' => 'TEPUNG',
                'price' => 9800,
                'product_type_id' => 1,
                'product_sub_type_id' => 2,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Tepung Beras',
                'code' => 'PRD004',
                'unit' => 'KG',
                'category' => 'TEPUNG',
                'price' => 11000,
                'product_type_id' => 1,
                'product_sub_type_id' => 3,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Gula Pasir',
                'code' => 'PRD005',
                'unit' => 'KG',
                'category' => 'NON_TEPUNG',
                'price' => 16500,
                'product_type_id' => 1,
                'product_sub_type_id' => 5,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Minyak Goreng',
                'code' => 'PRD006',
                'unit' => 'LTR',
                'category' => 'NON_TEPUNG',
                'price' => 20500,
                'product_type_id' => 1,
                'product_sub_type_id' => 5,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Garam Halus',
                'code' => 'PRD007',
                'unit' => 'KG',
                'category' => 'NON_TEPUNG',
                'price' => 7200,
                'product_type_id' => 1,
                'product_sub_type_id' => 3,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Plastik Kemasan 1 Kg',
                'code' => 'PRD008',
                'unit' => 'PCS',
                'category' => 'NON_TEPUNG',
                'price' => 850,
                'product_type_id' => 2,
                'product_sub_type_id' => 2,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Karung 25 Kg',
                'code' => 'PRD009',
                'unit' => 'PCS',
                'category' => 'NON_TEPUNG',
                'price' => 4500,
                'product_type_id' => 2,
                'product_sub_type_id' => 2,
                'vendor_id' => rand(1, 10),
            ],
            [
                'name' => 'Lakban Coklat',
                'code' => 'PRD010',
                'unit' => 'ROLL',
                'category' => 'NON_TEPUNG',
                'price' => 12000,
                'product_type_id' => 4,
                'product_sub_type_id' => 5,
                'vendor_id' => rand(1, 10),
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
