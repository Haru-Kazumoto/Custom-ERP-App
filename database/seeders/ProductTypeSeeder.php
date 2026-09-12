<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Raw Material',      'code' => 'PT001'],
            ['name' => 'Packaging',         'code' => 'PT002'],
            ['name' => 'Finished Goods',    'code' => 'PT003'],
            ['name' => 'Consumable',        'code' => 'PT004'],
            ['name' => 'Spare Part',        'code' => 'PT005'],
        ];

        foreach ($types as $type) {
            ProductType::create($type);
        }
    }
}
