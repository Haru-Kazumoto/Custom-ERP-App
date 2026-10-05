<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenus;
use Illuminate\Database\Seeder;

class RoleMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $procurement = Role::where('code', 'procurement')->first();
        $menus = Menu::all();

        collect($menus)->each(function ($data) use ($procurement) {
            RoleMenus::create([
                'menu_id' => $data->id,
                'role_id' => $procurement->id,
            ]);
        });
    }
}
