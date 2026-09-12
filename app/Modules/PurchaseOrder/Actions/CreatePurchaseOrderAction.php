<?php

namespace App\Modules\PurchaseOrder\Actions;

use App\Modules\PurchaseOrder\DTOs\CreatePurchaseOrderDTO;
use App\Modules\PurchaseOrder\Repositories\PurchaseOrderRepository;

class CreatePurchaseOrderAction
{
    public function __construct(private PurchaseOrderRepository $repository)
    {}

    public function execute(CreatePurchaseOrderDTO $dto)
    {
        return $this->repository->create($dto);
    }
}
