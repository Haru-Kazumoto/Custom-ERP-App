<?php

namespace App\Modules\Vendor\Queries;

use App\Modules\Vendor\Repositories\VendorRepository;

class GetVendorsQuery
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private VendorRepository $repository
    ){}

    public function execute()
    {
        return $this->repository->getAll();
    }
}
