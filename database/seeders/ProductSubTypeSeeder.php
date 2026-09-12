<?php

namespace Database\Seeders;

use App\Models\ProductSubType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSubTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subTypes = [
            ['name' => 'Premium',    'code' => 'PST001'],
            ['name' => 'Standard',   'code' => 'PST002'],
            ['name' => 'Economy',    'code' => 'PST003'],
            ['name' => 'Imported',   'code' => 'PST004'],
            ['name' => 'Local',      'code' => 'PST005'],
        ];

        foreach ($subTypes as $subType) {
            ProductSubType::create($subType);
        }
    }
}
