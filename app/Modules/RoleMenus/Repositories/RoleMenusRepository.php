<?php

namespace App\Modules\RoleMenus\Repositories;

use Illuminate\Support\Facades\DB;

class RoleMenusRepository
{
    /**
     * Menu milik suatu role, untuk sidebar.
     */
    public function getMenusByRoleId(int $roleId): array
    {
        return DB::table('role_menus as rm')
            ->join('menus as m', 'm.id', '=', 'rm.menu_id')
            ->where('rm.role_id', $roleId)
            ->select('m.*')
            ->get()
            ->map(function ($menu) {
                // Kolom JSON tidak di-cast Query Builder, jadi decode manual
                // supaya frontend selalu menerima array.
                $menu->active_routes = json_decode($menu->active_routes ?? '[]', true) ?: [];

                return $menu;
            })
            ->toArray();
    }

    /**
     * @return array<int, int>
     */
    public function getMenuIdsByRoleId(int $roleId): array
    {
        return DB::table('role_menus')
            ->where('role_id', $roleId)
            ->pluck('menu_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Samakan menu yang ter-attach ke `$roleId` persis dengan `$menuIds`.
     *
     * Idempoten dan tidak men-churn baris yang tidak berubah: hanya baris yang
     * baru ditambahkan dan baris yang harus dilepas yang tersentuh. Dipanggil
     * dalam `DB::transaction` supaya tidak pernah menyisakan keadaan setengah.
     *
     * `role_menus` tidak punya unique index di `(menu_id, role_id)`, jadi insert
     * disaring terhadap `alreadyAttached` agar satu klik "Simpan" dua kali tidak
     * menghasilkan baris kembar.
     *
     * @param  array<int, int>  $menuIds
     * @return array{attached: int, detached: int}
     */
    public function syncMenusForRole(int $roleId, array $menuIds): array
    {
        $existing = DB::table('role_menus')
            ->where('role_id', $roleId)
            ->pluck('menu_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $target = array_values(array_unique(array_map('intval', $menuIds)));
        sort($target);

        $toAttach = array_values(array_diff($target, $existing));
        $toDetach = array_values(array_diff($existing, $target));

        if ($toAttach !== []) {
            DB::table('role_menus')->insert(array_map(fn (int $menuId) => [
                'menu_id' => $menuId,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $toAttach));
        }

        if ($toDetach !== []) {
            DB::table('role_menus')
                ->where('role_id', $roleId)
                ->whereIn('menu_id', $toDetach)
                ->delete();
        }

        return [
            'attached' => count($toAttach),
            'detached' => count($toDetach),
        ];
    }
}
