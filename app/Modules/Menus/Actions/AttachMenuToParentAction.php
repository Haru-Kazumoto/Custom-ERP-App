<?php

namespace App\Modules\Menus\Actions;

use App\Models\Menu;
use App\Modules\Menus\Repositories\MenusRepository;
use Illuminate\Validation\ValidationException;

class AttachMenuToParentAction
{
    public function __construct(
        private MenusRepository $repository
    ) {}

    /**
     * Pasang sebuah menu sebagai anak dari `$parentId`.
     *
     * Menolak parent yang itu dirinya sendiri atau keturunannya sendiri. Siklus
     * seperti ini tidak bisa diselesaikan `buildMenuTree` di frontend, dan
     * akibatnya menu yang terlibat tidak muncul sama sekali di sidebar.
     */
    public function execute(int $menuId, int $parentId): Menu
    {
        if ($this->repository->isSelfOrDescendant($menuId, $parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Menu tidak bisa dijadikan anak dari dirinya sendiri atau keturunannya.',
            ]);
        }

        $this->repository->setParent($menuId, $parentId);

        return $this->repository->find($menuId);
    }
}
