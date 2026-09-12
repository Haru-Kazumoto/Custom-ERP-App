<?php

namespace Database\Seeders;

use App\Models\SubRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Sales Supervisor', 'code' => 'sales_supervisor', 'role_id' => 1],
            ['name' => 'Sales Manager', 'code' => 'sales_manager', 'role_id' => 1],
            ['name' => 'Salesman', 'code' => 'salesman', 'role_id' => 1],
            ['name' => 'Marketing DNP', 'code' => 'marketing_dnp', 'role_id' => 2],
            ['name' => 'Marketing DKU', 'code' => 'marketing_dku', 'role_id' => 2],
        ];

        collect($data)->each(function ($item) {
            SubRole::create($item);
        });
    }
}
