<?php

namespace App\Modules\DeliveryOrder\DTOs;

class DeliveryOrderItemDTO
{
    /**
     * @param  int  $quantity  Jumlah barang; divalidasi terhadap stok FEFO,
     *                         range promo, dan `base_quota` saat submit.
     * @param  float  $unit_price  Harga satuan manual (hanya dipakai kalau
     *                             `use_manual_price` true); kalau tidak, harga
     *                             diambil server dari `product_prices` sesuai
     *                             segmen terpilih.
     * @param  bool  $use_manual_price  Mode harga baris ini; dipilih per
     *                                  barang di form (default otomatis).
     * @param  int|null  $promo_product_id  Program promo yang dipilih user
     *                                      (`promo_products.id`); null = tanpa
     *                                      promo.
     */
    public function __construct(
        public readonly int $product_id,
        public readonly int $quantity,
        public readonly float $unit_price,
        public readonly bool $use_manual_price,
        public readonly ?int $promo_product_id,
    ) {}
}
