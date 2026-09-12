<?php

namespace App\Modules\Product\Queries;

use App\Modules\Product\Repositories\ProductRepository;

class GetProductQuery
{
    public function __construct(
        private ProductRepository $repository
    )
    {}

    public function execute(?string $search = null)
    {
        return $this->repository->getAll($search);
    }
}
