<?php

namespace App\Modules\RoleMenus\Queries;

use App\Modules\RoleMenus\Repositories\RoleMenusRepository;
use Illuminate\Support\Collection;

class GetMenusByIdRoleQuery
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private RoleMenusRepository $repository
    )
    {}

    public function execute(int $roleId): array
    {
        return $this->repository->getMenusByRoleId($roleId);
    }
}
