<?php

namespace App\Modules\RoleMenus\Repositories;

use App\Models\RoleMenus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleMenusRepository
{
    public function getMenusByRoleId(int $roleId): array
    {
        return DB::table('role_menus', 'rm')
            ->join('menus as m','m.id','=','rm.menu_id')
            ->where('rm.role_id', $roleId)
            ->get()
            ->toArray();
    }
}
