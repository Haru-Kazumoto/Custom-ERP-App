<?php

namespace App\Modules\DeliveryOrder\Actions;

use App\Modules\DeliveryOrder\Repositories\DeliveryOrderRepository;

class GenerateDeliveryOrderApprovalsAction
{
    public function __construct(private DeliveryOrderRepository $repository)
    {}

    /**
     * @param  int  $creator_id  Pembuat dokumen (`transactions.created_by`);
     *                            menentukan rantai approval sales lewat
     *                            sub-role dan hierarki `sub_roles.parent_id`.
     * @param  bool  $needs_bd_approval  Ada baris harga/diskon manual di form.
     */
    public function execute(int $delivery_order_id, int $creator_id, bool $needs_bd_approval): void
    {
        $this->repository->generateApprovals($delivery_order_id, $creator_id, $needs_bd_approval);
    }
}
