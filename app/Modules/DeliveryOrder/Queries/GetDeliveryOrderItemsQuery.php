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
 *
 * Dengan `$customer_id`, tiap baris juga membawa snapshot konfigurasi promo
 * (`promo`) yang dipakai form revisi untuk mempratinjau ulang harga setelah
 * diskon. Snapshot sengaja diambil tanpa memeriksa periode aktif: kalau
 * programnya sudah lewat tanggal, form harus tetap menampilkan promo yang
 * pernah dipakai — kalau di sini saja promonya hilang, submit revisi akan
 * menghapus promo itu secara diam-diam dan mengubah total tanpa pemberitahuan.
 * Pemeriksaan periode tetap dilakukan server saat submit.
 */
class GetDeliveryOrderItemsQuery
{
    public function __construct(private GetEligiblePromosQuery $eligible_promos) {}

    public function execute(int $delivery_order_id, ?int $customer_id = null)
    {
        $items = DB::table('transaction_items as ti')
            ->select(
                'ti.id',
                'ti.base_price',
                'ti.total_price',
                'ti.quantity',
                'ti.product_id',
                'ti.promo_product_id',
                'ti.use_manual_price',
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

        $promo_configs = $customer_id === null || $items->isEmpty()
            ? []
            : $this->eligible_promos->execute(
                $items->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values()->all(),
                $customer_id,
                false,
            );

        return $items->map(function ($item) use ($discounts, $promo_configs) {
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

            $item->promo = $this->promoSnapshot(
                (int) $item->product_id,
                $item->promo_product_id === null ? null : (int) $item->promo_product_id,
                $promo_configs,
            );

            return $item;
        });
    }

    /**
     * Snapshot konfigurasi promo satu baris, bentuk yang sama dengan payload
     * `GET /delivery-order/products` supaya form revisi bisa memakainya
     * langsung sebagai `DeliveryPromo`.
     *
     * @param  array<string, object>  $promo_configs  Key `{product_id}:{promo_product_id}`.
     * @return array<string, mixed>|null
     */
    private function promoSnapshot(int $product_id, ?int $promo_product_id, array $promo_configs): ?array
    {
        if ($promo_product_id === null) {
            return null;
        }

        $config = $promo_configs[$product_id.':'.$promo_product_id] ?? null;

        if ($config === null) {
            return null;
        }

        return [
            'promo_product_id' => $promo_product_id,
            'name' => (string) $config->promo_name,
            'code' => (string) $config->promo_code,
            'type' => (string) $config->promo_type,
            'min_qty' => $config->min_qty === null ? null : (int) $config->min_qty,
            'max_qty' => $config->max_qty === null ? null : (int) $config->max_qty,
            'base_quota' => $config->base_quota === null ? null : (int) $config->base_quota,
            'percentage_1' => $config->percentage_1 === null ? null : (float) $config->percentage_1,
            'percentage_2' => $config->percentage_2 === null ? null : (float) $config->percentage_2,
            'percentage_3' => $config->percentage_3 === null ? null : (float) $config->percentage_3,
            'manual_type' => $config->manual_type,
            'manual_percentage' => $config->manual_percentage === null ? null : (float) $config->manual_percentage,
            'manual_value' => $config->manual_value === null ? null : (float) $config->manual_value,
        ];
    }
}
