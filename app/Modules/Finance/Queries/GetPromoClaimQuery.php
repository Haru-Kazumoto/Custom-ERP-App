<?php

namespace App\Modules\Finance\Queries;

use Illuminate\Support\Facades\DB;

class GetPromoClaimQuery
{
    /**
     * Klaim promo yang sudah tercatat.
     *
     * Membaca tabel `promo_claim` apa adanya. Catatan penting: kolomnya
     * (`distributor_name`, `area`, `program`) berorientasi klaim sisi sales, bukan
     * klaim promotrade ke pemasok barang, jadi tabel ini belum memenuhi
     * kebutuhan modul ini dan hanya ditampilkan sebagai data yang ada.
     */
    public function execute(int $limit = 25): array
    {
        $limit = max(1, min($limit, 200));

        return DB::table('promo_claim')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'claim_number',
                'month',
                'distributor_name',
                'area',
                'program',
                'sub_total',
                'grand_total',
            ])
            ->map(function ($row) {
                $row->id = (int) $row->id;
                $row->sub_total = (float) $row->sub_total;
                $row->grand_total = (float) $row->grand_total;

                return $row;
            })
            ->toArray();
    }
}
