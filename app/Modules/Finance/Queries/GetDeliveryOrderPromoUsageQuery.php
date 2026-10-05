<?php

namespace App\Modules\Finance\Queries;

use Illuminate\Support\Facades\DB;

class GetDeliveryOrderPromoUsageQuery
{
    /**
     * Promo trade yang terpakai pada setiap baris delivery order.
     *
     * Sumbernya `receiving_items` -> `transaction_items`, karena baris DO hanya
     * menunjuk item PO/transaksi; `trade_promo_id` yang dipakai penentuan promo
     * ada di item tersebut.
     *
     * Tabel `receivings` masih kosong selama modul delivery order belum dibuat,
     * jadi query ini sengaja tetap memakai skema yang sudah ada: begitu modulnya
     * hidup, daftar ini terisi tanpa perubahan kode.
     *
     * Nilai promo tidak dihitung. Harga katalog tidak disimpan per item —
     * `transaction_items.base_price` sudah harga bersih hasil Diskon PO — sehingga
     * selisih terhadap `trade_promo.price` tidak bisa dipakai sebagai angka klaim
     * yang dapat dipertanggungjawabkan. Nominal klaim ditentukan saat pengajuan.
     */
    public function execute(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));

        return DB::table('receiving_items as ri')
            ->join('receivings as rc', 'rc.id', '=', 'ri.receiving_id')
            ->join('transactions as tx', 'tx.id', '=', 'rc.transaction_id')
            ->join('transaction_items as ti', 'ti.id', '=', 'ri.transaction_items_id')
            ->join('products as p', 'p.id', '=', 'ti.product_id')
            ->join('trade_promo as tp', 'tp.id', '=', 'ti.trade_promo_id')
            ->leftJoin('transaction_details as td', function ($join) {
                $join->on('td.transaction_id', '=', 'tx.id')
                    ->where('td.type', 'SUPPLIER');
            })
            ->orderByDesc('rc.received_at')
            ->orderByDesc('ri.id')
            ->limit($limit)
            ->get([
                'ri.id as receiving_item_id',
                'ri.received_qty',
                'rc.id as receiving_id',
                'rc.received_at',
                'rc.status as receiving_status',
                'tx.id as transaction_id',
                'tx.transaction_code',
                'td.value as vendor_name',
                'p.name as product_name',
                'p.code as product_code',
                'tp.id as trade_promo_id',
                'tp.name as trade_promo_name',
                'ti.quantity as ordered_qty',
                'ti.total_price as line_total',
            ])
            ->map(function ($row) {
                $row->receiving_item_id = (int) $row->receiving_item_id;
                $row->receiving_id = (int) $row->receiving_id;
                $row->transaction_id = (int) $row->transaction_id;
                $row->trade_promo_id = (int) $row->trade_promo_id;
                $row->received_qty = (int) $row->received_qty;
                $row->ordered_qty = (int) $row->ordered_qty;
                $row->line_total = (float) $row->line_total;

                return $row;
            })
            ->toArray();
    }
}
