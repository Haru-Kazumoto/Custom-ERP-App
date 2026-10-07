<?php

namespace App\Modules\DeliveryOrder\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Opsi form Delivery Order: perusahaan (gudang), jenis pengiriman + sub,
 * dan pelanggan.
 *
 * Semua referensi DO belum punya module sendiri (Vendor/Transportation punya,
 * tapi Shipping/Customer belum), jadi dibaca langsung di sini supaya tidak ada
 * class satu-method yang hanya memanggil `DB::table()`.
 */
class GetDeliveryOrderFormOptionsQuery
{
    public function execute(): array
    {
        $shippings = DB::table('shippings')
            ->orderBy('id')
            ->get(['id', 'code', 'name'])
            ->map(function ($shipping) {
                $shipping->subs = DB::table('sub_shippings')
                    ->where('shipping_id', $shipping->id)
                    ->orderBy('id')
                    ->get(['id', 'code', 'name', 'shipping_id']);

                return $shipping;
            });

        return [
            'companies' => DB::table('companies')->orderBy('id')->get(['id', 'code', 'name']),
            'shippings' => $shippings,
            // Pelanggan dikirim lengkap (id, nama, segmen, termin): form
            // menampilkan segmen sebagai label harga dan memakai `term_payment`
            // untuk menghitung jatuh tempo.
            'customers' => DB::table('customers')
                ->orderBy('name')
                ->get(['id', 'name', 'segment', 'term_payment', 'salesman_id']),
        ];
    }
}
