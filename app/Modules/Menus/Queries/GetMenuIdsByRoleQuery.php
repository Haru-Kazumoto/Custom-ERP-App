<?php

namespace App\Modules\Menus\Queries;

use App\Modules\RoleMenus\Repositories\RoleMenusRepository;

class GetMenuIdsByRoleQuery
{
    public function __construct(
        private RoleMenusRepository $repository
    ) {}

    /**
     * Daftar `menu_id` yang sudah ter-attach ke suatu role.
     *
     * ManageRole memakainya untuk menandai checkbox mana yang sudah tercentang.
     *
     * @return array<int, int>
     */
    public function execute(int $roleId): array
    {
        return $this->repository->getMenuIdsByRoleId($roleId);
    }
}
