<?php

namespace App\Modules\DeliveryOrder\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Promo yang eligible untuk satu pelanggan pada saat dokumen dibuat.
 *
 * Sumber: `assigned_products_promo` (konfigurasi diskon per produk) yang
 * di-join ke `promo_products` (program: nama, jenis, periode aktif).
 *
 * Baris promo terikat pelanggan lewat `assigned_customer_promo_id`; NULL
 * berarti promo berlaku untuk semua pelanggan.
 *
 * Periode aktif difilter di sini, jadi promo kadaluarsa tidak pernah
 * sampai ke form maupun ke kalkulator submit.
 */
class GetEligiblePromosQuery
{
    /**
     * @param  int[]  $productIds
     * @param  bool  $within_active_period  false = abaikan `start_date`/
     *                                       `end_date`. Dipakai membaca ulang
     *                                       promo dokumen lama (form revisi),
     *                                       di mana promo yang sudah lewat
     *                                       tetap harus tampil - kalau tidak,
     *                                       promo hilang dari form dan submit
     *                                       berikutnya mengubah total tanpa
     *                                       pemberitahuan. Jalur submit selalu
     *                                       memakai `true`.
     * @return array<string, object>  Keyed by "{product_id}:{promo_product_id}".
     */
    public function execute(array $productIds, int $customerId, bool $within_active_period = true): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('assigned_products_promo as app')
            ->join('promo_products as pp', 'pp.id', '=', 'app.promo_product_id')
            ->leftJoin('assigned_customer_promo as acp', 'acp.id', '=', 'app.assigned_customer_promo_id')
            ->where(function ($query) use ($productIds) {
                $query->whereIn('app.product_id', $productIds);
            })
            ->where(function ($query) use ($customerId) {
                $query->whereNull('app.assigned_customer_promo_id')
                    ->orWhere('acp.customer_id', $customerId);
            })
            ->when($within_active_period, function ($query) {
                $query->where('pp.start_date', '<=', now())
                    ->where('pp.end_date', '>=', now());
            })
            ->get([
                'app.id as assigned_id',
                'app.product_id',
                'app.promo_product_id',
                'app.assigned_customer_promo_id',
                'app.min_qty',
                'app.max_qty',
                'app.base_quota',
                'app.percentage_1',
                'app.percentage_2',
                'app.percentage_3',
                'app.manual_type',
                'app.manual_percentage',
                'app.manual_value',
                'pp.name as promo_name',
                'pp.code as promo_code',
                'pp.type as promo_type',
            ]);

        $result = [];

        foreach ($rows as $row) {
            // Satu produk boleh punya lebih dari satu program promo; keduanya
            // dikirim ke form sebagai opsi. Kalau program yang sama dipetakan
            // dua kali untuk produk yang sama, baris pertama yang menang.
            $result[$row->product_id.':'.$row->promo_product_id] ??= $row;
        }

        return $result;
    }
}
