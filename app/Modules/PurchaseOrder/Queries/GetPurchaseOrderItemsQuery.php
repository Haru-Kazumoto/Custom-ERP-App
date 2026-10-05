<?php

namespace App\Modules\PurchaseOrder\Queries;

use Illuminate\Support\Facades\DB;

class GetPurchaseOrderItemsQuery
{
    /**
     * Baris item PO lengkap dengan label produknya.
     *
     * Dipakai halaman detail, dan dipakai juga untuk mengisi ulang form revisi
     * supaya user mengoreksi dokumen yang sudah ada, bukan mengetik ulang dari
     * nol (yang membuat nomor, tanggal, dan baris yang tidak salah ikut hilang).
     *
     * Kolom `trade_promo_id` ikut dikembalikan hanya untuk form revisi. Halaman
     * detail tidak pernah memakai kolom itu langsung; label promo (`trade_promo`)
     * yang dipakainya.
     */
    public function execute(int $purchase_order_id)
    {
        return DB::table('transaction_items as ti')
            ->select(
                'ti.id',
                'ti.base_price',
                'ti.total_price',
                'ti.quantity',
                'ti.product_id',
                'ti.trade_promo_id',
                'p.name as product_name',
                'p.unit as product_unit',
                'p.category as product_category',
                'p.code as product_code',
                // Harga katalog. Dikembalikan supaya form revisi bisa menampilkan
                // harga sebelum promo; `trade_promo_price` saja tidak cukup karena
                // itu harga promo, bukan harga sebelum diskon.
                'p.price as product_price',
                'tp.name as trade_promo',
                'tp.price as trade_promo_price'
            )
            ->leftJoin('products as p', 'p.id', '=', 'ti.product_id')
            ->leftJoin('trade_promo as tp', 'tp.id', '=', 'ti.trade_promo_id')
            ->where('ti.transaction_id', $purchase_order_id)
            ->get()
            ->map(function ($item) {
                // `unit_price` tidak disimpan. Yang tersimpan adalah
                // `base_price` (net, sudah dipisah PPN) dan `total_price`
                // (bruto per baris), karena `PurchaseOrderCalculator` tidak
                // menyimpan input mentahnya.
                //
                // Form revisi justru butuh harga BRUTO per satuan, karena
                // `PurchaseOrderCalculator::compute()` mengali
                // `unit_price × quantity` untuk `total_price`. Ambil dari
                // `total_price` satu baris, bukan `base_price`: memakai
                // `base_price` akan memotong PPN dua kali — dari `unit_price`
                // untuk `base_price` lagi di kalkulator, sehingga total baris
                // yang tersimpan berubah padahal tidak ada yang diedit.
                $item->unit_price = (int) $item->quantity > 0
                    ? round((float) $item->total_price / (int) $item->quantity, 2)
                    : (float) $item->base_price;

                return $item;
            });
    }
}
