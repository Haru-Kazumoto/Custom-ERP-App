<?php

namespace App\Modules\Transportation\Queries;

use App\Modules\Transportation\Repositories\TransportationRepository;

class GetTransportationsQuery
{
    public function __construct(
        private TransportationRepository $repository
    )
    {}

    public function execute()
    {
        return $this->repository->getAll();
    }
}
