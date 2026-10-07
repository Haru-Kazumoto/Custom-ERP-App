<?php

namespace App\Modules\DeliveryOrder\Actions;

use App\Modules\DeliveryOrder\DTOs\CreateDeliveryOrderDTO;
use App\Modules\DeliveryOrder\Repositories\DeliveryOrderRepository;

class CreateDeliveryOrderAction
{
    public function __construct(private DeliveryOrderRepository $repository)
    {}

    /**
     * @return array{transaction: \App\Models\Transaction, lines: array<int, array<string, mixed>>}
     */
    public function execute(CreateDeliveryOrderDTO $dto): array
    {
        return $this->repository->create($dto);
    }
}
