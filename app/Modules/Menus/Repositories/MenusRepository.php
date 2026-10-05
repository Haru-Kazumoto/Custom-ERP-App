<?php

namespace App\Modules\Menus\Repositories;

use App\Models\Menu;

class MenusRepository
{
    /**
     * Daftar menu datar beserta nama parent-nya.
     *
     * Flat (bukan tree) karena UI Index butuh satu baris per menu dengan kolom
     * "Parent" yang terisi. `parent_name` diambil lewat LEFT JOIN supaya menu
     * tanpa parent tetap ikut dan `parent_id` NULL tidak berubah jadi string kosong.
     */
    public function getAllWithParent(): array
    {
        return Menu::query()
            ->leftJoin('menus as parent', 'parent.id', '=', 'menus.parent_id')
            ->select('menus.*')
            ->addSelect('parent.name as parent_name')
            ->orderBy('menus.name')
            ->get()
            ->map(function (Menu $menu) {
                $menu->parent_name = $menu->parent_name ?? null;

                return $menu;
            })
            ->toArray();
    }

    /**
     * Kandidat parent.
     *
     * Kalau `$excludeId` diisi, menu itu sendiri dan seluruh keturunannya
     * dikeluarkan supaya tidak bisa dipilih — memilih keturunannya akan membuat
     * siklus, dan `buildMenuTree` di frontend tidak bisa menyelesaikan siklus
     * sehingga menu tersebut hilang dari sidebar.
     */
    public function getParentOptions(?int $excludeId = null): array
    {
        $excluded = $excludeId ? $this->getSelfAndDescendantIds($excludeId) : [];

        return Menu::query()
            ->orderBy('name')
            ->get()
            ->reject(fn (Menu $menu) => in_array($menu->id, $excluded, true))
            ->map(fn (Menu $menu) => [
                'label' => $menu->name,
                'value' => $menu->id,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Peta { menu_id => [id anak, id cucu, ...] } untuk semua menu.
     *
     * Dipakai ManageMenu supaya opsi parent yang akan membentuk siklus bisa
     * dinonaktifkan di UI, sementara guard siklus tetap ada di server.
     */
    public function getDescendantMap(): array
    {
        $childrenOf = [];
        foreach (Menu::query()->get(['id', 'parent_id']) as $menu) {
            if ($menu->parent_id !== null) {
                $childrenOf[$menu->parent_id][] = $menu->id;
            }
        }

        $map = [];
        foreach (Menu::query()->pluck('id') as $id) {
            $map[$id] = $this->collectDescendants($id, $childrenOf);
        }

        return $map;
    }

    public function find(int $id): ?Menu
    {
        return Menu::query()->find($id);
    }

    public function exists(int $id): bool
    {
        return Menu::query()->whereKey($id)->exists();
    }

    public function create(array $attributes): Menu
    {
        return Menu::query()->create($attributes);
    }

    /**
     * Set `parent_id` sebuah menu. `$parentId` null berarti lepas parent.
     */
    public function setParent(int $menuId, ?int $parentId): bool
    {
        return (bool) Menu::query()
            ->whereKey($menuId)
            ->update(['parent_id' => $parentId]);
    }

    /**
     * Apakah `$candidateId` adalah `$ancestorId` sendiri atau anak turunannya?
     *
     * Menelusuri rantai `parent_id` ke atas. Ada batas iterasi supaya data siklus
     * yang terlanjur ada di database tidak menyebabkan loop tak terbatas.
     */
    public function isSelfOrDescendant(int $ancestorId, int $candidateId): bool
    {
        $currentId = $candidateId;

        for ($step = 0; $step < 100 && $currentId !== null; $step++) {
            if ($currentId === $ancestorId) {
                return true;
            }

            $currentId = Menu::query()->whereKey($currentId)->value('parent_id');
        }

        return false;
    }

    /**
     * Gabungan id menu itu sendiri dengan seluruh keturunannya.
     *
     * @return array<int, int>
     */
    public function getSelfAndDescendantIds(int $id): array
    {
        $childrenOf = [];
        foreach (Menu::query()->get(['id', 'parent_id']) as $menu) {
            if ($menu->parent_id !== null) {
                $childrenOf[$menu->parent_id][] = $menu->id;
            }
        }

        return array_values(array_unique(array_merge([$id], $this->collectDescendants($id, $childrenOf))));
    }

    /**
     * @param  array<int, int[]>  $childrenOf
     * @return array<int, int>
     */
    private function collectDescendants(int $id, array $childrenOf): array
    {
        $collected = [];

        foreach ($childrenOf[$id] ?? [] as $childId) {
            if (in_array($childId, $collected, true)) {
                continue;
            }

            $collected[] = $childId;

            foreach ($this->collectDescendants($childId, $childrenOf) as $grandChildId) {
                if (! in_array($grandChildId, $collected, true)) {
                    $collected[] = $grandChildId;
                }
            }
        }

        return $collected;
    }
}
