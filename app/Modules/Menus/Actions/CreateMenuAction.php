<?php

namespace App\Modules\Menus\Actions;

use App\Models\Menu;
use App\Modules\Menus\DTOs\CreateMenuDTO;
use App\Modules\Menus\Repositories\MenusRepository;

class CreateMenuAction
{
    public function __construct(
        private MenusRepository $repository
    ) {}

    public function execute(CreateMenuDTO $dto): Menu
    {
        return $this->repository->create($dto->toAttributes());
    }
}
