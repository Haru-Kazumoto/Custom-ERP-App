<?php

namespace App\Modules\DeliveryOrder\Queries;

use App\Modules\DeliveryOrder\Services\DeliveryOrderPriceResolver;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian barang untuk form Delivery Order.
 *
 * Kandidat: produk yang stoknya POSITIF di gudang terpilih (aggregate
 * `product_journals` = SUM(IN) − SUM(OUT)), lengkap dengan harga yang berlaku
 * untuk kombinasi pengiriman + segmen pelanggan dan daftar promo yang eligible.
 *
 * `has_price` / `price` hanya informasi untuk men-disable tombol tambah di
 * form; server menghitung ulang harga, promo, dan stok dari database saat
 * submit, jadi angka kiriman browser tidak pernah dipercaya.
 */
class GetDeliveryOrderProductsQuery
{
    public function __construct(
        private DeliveryOrderPriceResolver $prices,
        private GetEligiblePromosQuery $promos,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     company_id?: int|null,
     *     shipping_id?: int|null,
     *     sub_shipping_id?: int|null,
     *     customer_id?: int|null,
     *     segment?: string|null
     * }  $filters
     * @return array<int, object>
     */
    public function execute(array $filters = []): array
    {
        $companyId = (int) ($filters['company_id'] ?? 0);

        if ($companyId <= 0) {
            return [];
        }

        $search = trim((string) ($filters['search'] ?? ''));

        $stock = "SUM(CASE WHEN pj.action = 'IN' THEN pj.quantity ELSE -pj.quantity END)";

        $query = DB::table('product_journals as pj')
            ->join('products as p', 'p.id', '=', 'pj.product_id')
            ->where('pj.company_id', $companyId)
            ->groupBy('p.id', 'p.code', 'p.name', 'p.unit', 'p.category')
            ->havingRaw("{$stock} > 0")
            ->selectRaw("
                p.id,
                p.code,
                p.name,
                p.unit,
                p.category,
                {$stock} as stock
            ")
            ->orderBy('p.code');

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('p.code', 'like', "%{$search}%")
                    ->orWhere('p.name', 'like', "%{$search}%");
            });
        }

        $products = $query->limit(100)->get();

        if ($products->isEmpty()) {
            return [];
        }

        $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $customer_id = (int) ($filters['customer_id'] ?? 0);
        $promoConfigs = $customer_id > 0
            ? $this->promos->execute($productIds, $customer_id)
            : [];

        $shippingId = (int) ($filters['shipping_id'] ?? 0);
        $subShippingId = isset($filters['sub_shipping_id']) && $filters['sub_shipping_id'] !== null
            ? (int) $filters['sub_shipping_id']
            : null;

        // Kelompokkan promo per produk untuk payload form.
        $promosByProduct = [];
        foreach ($promoConfigs as $config) {
            $promosByProduct[(int) $config->product_id][] = [
                'promo_product_id' => (int) $config->promo_product_id,
                'name' => $config->promo_name,
                'code' => $config->promo_code,
                'type' => $config->promo_type,
                'min_qty' => $config->min_qty === null ? null : (int) $config->min_qty,
                'max_qty' => $config->max_qty === null ? null : (int) $config->max_qty,
                'base_quota' => $config->base_quota === null ? null : (int) $config->base_quota,
                // Konfigurasi cascading ikut dikirim supaya form bisa
                // mempratinjau harga setelah promo (server tetap menghitung
                // ulang dan memvalidasi saat submit).
                'percentage_1' => $config->percentage_1 === null ? null : (float) $config->percentage_1,
                'percentage_2' => $config->percentage_2 === null ? null : (float) $config->percentage_2,
                'percentage_3' => $config->percentage_3 === null ? null : (float) $config->percentage_3,
                'manual_type' => $config->manual_type,
                'manual_percentage' => $config->manual_percentage === null ? null : (float) $config->manual_percentage,
                'manual_value' => $config->manual_value === null ? null : (float) $config->manual_value,
            ];
        }

        return $products->map(function ($product) use ($shippingId, $subShippingId, $filters, $promosByProduct) {
            $product->stock = (int) $product->stock;
            $product->promos = $promosByProduct[(int) $product->id] ?? [];

            if ($shippingId <= 0) {
                // Pengiriman belum dipilih → belum ada baris harga yang bisa
                // dicocokkan. Form menampilkan kolom harga sebagai "-".
                $product->has_price = false;
                $product->price = null;

                return $product;
            }

            try {
                $price = $this->prices->resolve(
                    (int) $product->id,
                    $shippingId,
                    $subShippingId,
                    $filters['segment'] ?? null,
                    (string) $product->name,
                );
            } catch (\RuntimeException) {
                // Baris `product_prices` tidak ada → tombol tambah dinonaktifkan
                // dan server juga akan menolak dokumen dengan pesan yang sama.
                $product->has_price = false;
                $product->price = null;

                return $product;
            }

            $product->has_price = true;
            $product->price = $price;

            return $product;
        })->all();
    }
}
