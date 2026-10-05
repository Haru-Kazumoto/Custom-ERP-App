<?php

namespace App\Modules\Product\Actions;

use App\Models\Product;
use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Repositories\ProductRepository;

class StoreProductAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function execute(ProductDTO $dto): Product
    {
        return Product::query()->create($dto->toAttributes());
    }
}
