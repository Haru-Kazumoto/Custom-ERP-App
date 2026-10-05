<?php

namespace App\Modules\Menus\Queries;

use App\Modules\Menus\Repositories\MenusRepository;

class GetMenusQuery
{
    public function __construct(
        private MenusRepository $repository
    ) {}

    /**
     * Semua menu datar, masing-masing sudah membawa `parent_name`.
     */
    public function execute(): array
    {
        return $this->repository->getAllWithParent();
    }
}
