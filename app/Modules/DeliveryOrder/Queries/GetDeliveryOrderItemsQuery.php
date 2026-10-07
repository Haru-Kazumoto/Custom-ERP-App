<?php

namespace App\Modules\DeliveryOrder\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Baris item DO lengkap dengan label produk, nama program promo, dan
 * rincian diskon cascading per tahap (`transacton_item_discounts`).
 *
 * Rincian tahap dipakai halaman detail untuk menampilkan persentase diskon
 * (percentage_1/2/3 atau manual) dan nominal diskon per baris — angka yang
 * sama dengan yang pernah dihitung `DeliveryOrderCalculator` saat dokumen
 * dibuat.
 */
class GetDeliveryOrderItemsQuery
{
    public function execute(int $delivery_order_id)
    {
        $items = DB::table('transaction_items as ti')
            ->select(
                'ti.id',
                'ti.base_price',
                'ti.total_price',
                'ti.quantity',
                'ti.product_id',
                'ti.promo_product_id',
                'p.name as product_name',
                'p.unit as product_unit',
                'p.category as product_category',
                'p.code as product_code',
                'pp.name as promo_name',
                'pp.code as promo_code'
            )
            ->leftJoin('products as p', 'p.id', '=', 'ti.product_id')
            ->leftJoin('promo_products as pp', 'pp.id', '=', 'ti.promo_product_id')
            ->where('ti.transaction_id', $delivery_order_id)
            ->get();

        $discounts = $items->isEmpty()
            ? collect()
            : DB::table('transacton_item_discounts')
                ->whereIn('transaction_item_id', $items->pluck('id'))
                ->orderBy('sequence')
                ->get()
                ->groupBy('transaction_item_id');

        return $items->map(function ($item) use ($discounts) {
            // Harga satuan bruto setelah promo (gross ÷ qty), sama seperti
            // yang dipakai kalkulator saat menghitung `total_price`.
            $item->unit_price = (int) $item->quantity > 0
                ? round((float) $item->total_price / (int) $item->quantity, 2)
                : (float) $item->base_price;

            $stages = $discounts->get($item->id, collect());
            $item->discounts = $stages
                ->map(fn ($stage) => [
                    'sequence' => (int) $stage->sequence,
                    'discount_type' => (string) $stage->discount_type,
                    'discount_value' => (string) $stage->discount_value,
                    'source' => (string) $stage->source,
                ])
                ->values()
                ->all();

            // Harga satuan sebelum & sesudah promo per tahap; null untuk
            // baris tanpa promo supaya tampilan tidak menampilkan angka
            // "diskon" palsu.
            $item->unit_price_before = $stages->isEmpty()
                ? null
                : round((float) $stages->first()->amount_before_discount, 2);
            $item->unit_price_after = $stages->isEmpty()
                ? null
                : round((float) $stages->last()->amount_after_discount, 2);

            return $item;
        });
    }
}
