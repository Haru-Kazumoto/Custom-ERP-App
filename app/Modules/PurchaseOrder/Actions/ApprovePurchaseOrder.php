<?php

namespace App\Modules\PurchaseOrder\Actions;

use App\Modules\PurchaseOrder\DTOs\ApprovePurchaseOrderDTO;
use App\Modules\PurchaseOrder\Repositories\PurchaseOrderRepository;

class ApprovePurchaseOrder
{
    public function __construct(private PurchaseOrderRepository $repository)
    {}

    public function execute(ApprovePurchaseOrderDTO $dto)
    {
        return $this->repository->approve();
    }
}
