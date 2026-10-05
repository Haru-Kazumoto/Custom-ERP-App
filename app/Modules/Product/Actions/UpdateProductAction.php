<?php

namespace App\Modules\Product\Actions;

use App\Models\Product;
use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Repositories\ProductRepository;

class UpdateProductAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function execute(Product $product, ProductDTO $dto): Product
    {
        $this->repository->update((int) $product->id, $dto->toAttributes());

        return $product->refresh();
    }
}
