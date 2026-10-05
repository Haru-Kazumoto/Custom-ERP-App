<?php

namespace App\Modules\Product\Queries;

use App\Modules\Product\Repositories\ProductRepository;

class GetProductQuery
{
    /**
     * Batas atas jumlah katalog yang boleh diminta sekaligus.
     *
     * Tanpa clamp, `?limit=100000` di URL akan membuat endpoint menarik seluruh
     * tabel `products` — endpoint ini dipanggil setiap kali dropdown dibuka.
     */
    private const MAX_LIMIT = 50;

    public function __construct(
        private ProductRepository $repository
    ) {}

    /**
     * @param  array{category?: string|null, vendorId?: int|null}  $filters
     */
    public function execute(
        ?string $search = null,
        int $limit = 20,
        array $filters = [],
    ) {
        return $this->repository->getAll(
            $search,
            max(1, min($limit, self::MAX_LIMIT)),
            $filters,
        );
    }
}
