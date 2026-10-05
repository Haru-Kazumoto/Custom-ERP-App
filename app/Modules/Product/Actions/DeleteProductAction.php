<?php

namespace App\Modules\Product\Actions;

use App\Models\Product;
use App\Modules\Product\Repositories\ProductRepository;

class DeleteProductAction
{
    public function __construct(
        private ProductRepository $repository
    ) {}

    public function execute(Product $product): void
    {
        // Hard delete. Baris `product_prices` produk ini ikut terhapus karena
        // FK-nya `cascadeOnDelete`.
        $this->repository->delete((int) $product->id);
    }
}
