<?php

namespace App\Modules\DeliveryOrder\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sumber harga Delivery Order: `product_prices` sesuai segmen pelanggan.
 *
 * Kolom harga dipilih dari `customers.segment` (legacy memakai
 * `strtolower(segment) . "_price"`). Kalau baris harga untuk kombinasi
 * produk + pengiriman + sub tidak ada, transaksi harus ditolak — bukan
 * jatuh ke `products.price`, supaya tidak ada dokumen yang terjual tanpa
 * harga yang benar-benar dihitung.
 */
class DeliveryOrderPriceResolver
{
    /**
     * Pemetaan segmen → kolom `product_prices`. GROSIR dipetakan ke
     * `wholesale_price` karena tabel tidak punya kolom `grosir_price`.
     */
    private const SEGMENT_COLUMNS = [
        'RETAIL' => 'retail_price',
        'WHOLESALE' => 'wholesale_price',
        'GROSIR' => 'wholesale_price',
        'END_USER' => 'end_user_price',
        'ALL_SEGMENT' => 'all_segment_price',
    ];

    /**
     * @return float Harga satuan bruto (termasuk PPN bila dokumen memakai PPN).
     *
     * @throws RuntimeException kalau baris harga tidak tersedia.
     */
    public function resolve(int $productId, int $shippingId, ?int $subShippingId, ?string $segment, string $productName): float
    {
        $column = $this->priceColumn($segment);

        $row = DB::table('product_prices')
            ->where('product_id', $productId)
            ->where('shipping_id', $shippingId)
            ->where(function ($query) use ($subShippingId) {
                $subShippingId === null
                    ? $query->whereNull('sub_shipping_id')
                    : $query->where('sub_shipping_id', $subShippingId);
            })
            ->first();

        if ($row === null) {
            throw new RuntimeException(
                "Harga untuk produk \"{$productName}\" tidak tersedia untuk pengiriman yang dipilih. Hubungi tim pricing."
            );
        }

        $price = (float) $row->{$column};

        if ($price <= 0) {
            throw new RuntimeException(
                "Harga untuk produk \"{$productName}\" masih 0. Hubungi tim pricing."
            );
        }

        return $price;
    }

    private function priceColumn(?string $segment): string
    {
        // Pelanggan tanpa segmen dianggap ALL_SEGMENT: segmen memang nullable
        // di `customers`, dan menolak transaksi hanya karena segmen kosong
        // akan memblokir pelanggan yang memang belum dikategorikan.
        $key = strtoupper(trim((string) ($segment ?: 'ALL_SEGMENT')));

        return self::SEGMENT_COLUMNS[$key]
            ?? throw new RuntimeException("Segmen pelanggan \"{$segment}\" tidak dikenali.");
    }
}
