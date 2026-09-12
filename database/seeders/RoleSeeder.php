<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Sales', 'code' => 'sales'],
            ['name' => 'Marketing', 'code' => 'marketing'],
            ['name' => 'Procurement', 'code' => 'procurement'],
            ['name' => 'Business Development', 'code' => 'business_development'],
            ['name' => 'Invoicing', 'code' => 'invoicing'],
            ['name' => 'Finance', 'code' => 'finance'],
            ['name' => 'Warehouse', 'code' => 'warehouse'],
            ['name' => 'AR Controller', 'code' => 'ar_controller'],
            ['name' => 'Document Control', 'code' => 'document_control'],
            ['name' => 'Admin', 'code' => 'admin']
        ];

        collect($data)->each(function ($item) {
            Role::create($item);
        });
    }
}
