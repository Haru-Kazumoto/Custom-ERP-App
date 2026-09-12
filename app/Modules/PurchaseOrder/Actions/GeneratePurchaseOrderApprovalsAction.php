<?php

namespace App\Modules\PurchaseOrder\Actions;

use App\Modules\PurchaseOrder\Repositories\PurchaseOrderRepository;

class GeneratePurchaseOrderApprovalsAction
{
    public function __construct(private PurchaseOrderRepository $repository)
    {}

    public function execute(int $purchase_order_id)
    {
        return $this->repository->generateApprovals($purchase_order_id);
    }
}
