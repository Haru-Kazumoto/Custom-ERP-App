<?php

namespace App\Modules\Menus\Actions;

use App\Models\Menu;
use App\Modules\Menus\Repositories\MenusRepository;

class DetachMenuFromParentAction
{
    public function __construct(
        private MenusRepository $repository
    ) {}

    /**
     * Lepas `$menuId` dari parent-nya sehingga jadi menu level atas.
     *
     * Menurut migrasi `menus`, `parent_id` nullable dan menunjuk ke diri sendiri,
     * jadi cukup dikosongkan — anak-anaknya tetap menempel ke menu ini.
     */
    public function execute(int $menuId): Menu
    {
        $this->repository->setParent($menuId, null);

        return $this->repository->find($menuId);
    }
}
