<?php

namespace App\Modules\Menus\Queries;

use App\Modules\Menus\Repositories\MenusRepository;

class GetParentOptionsQuery
{
    public function __construct(
        private MenusRepository $repository
    ) {}

    /**
     * Kandidat parent dalam bentuk `[{ label, value }]`.
     */
    public function execute(?int $excludeMenuId = null): array
    {
        return $this->repository->getParentOptions($excludeMenuId);
    }
}
