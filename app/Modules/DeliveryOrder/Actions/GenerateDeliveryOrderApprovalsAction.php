<?php

namespace App\Modules\DeliveryOrder\Actions;

use App\Modules\DeliveryOrder\Repositories\DeliveryOrderRepository;

class GenerateDeliveryOrderApprovalsAction
{
    public function __construct(private DeliveryOrderRepository $repository)
    {}

    public function execute(int $delivery_order_id): void
    {
        $this->repository->generateApprovals($delivery_order_id);
    }
}
