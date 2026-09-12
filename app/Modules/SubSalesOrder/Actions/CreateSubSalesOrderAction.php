<?php

namespace App\Modules\SubSalesOrder\Actions;

use App\Modules\SubSalesOrder\DTOs\CreateSubSalesOrderDTO;
use App\Modules\SubSalesOrder\Repositories\SubSalesOrderRepository;
use Illuminate\Support\Facades\DB;

class CreateSubSalesOrderAction
{
    public function __construct(protected SubSalesOrderRepository $repository)
    {}

    public function execute(CreateSubSalesOrderDTO $dto)
    {
        return DB::transaction(function () use ($dto) {
            return $this->repository->create($dto->toArray());
        });
    }
}
