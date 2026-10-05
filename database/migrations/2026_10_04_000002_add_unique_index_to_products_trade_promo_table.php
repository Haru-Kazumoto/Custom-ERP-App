<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cegah pasangan promo–produk yang sama ter-insert dua kali.
     *
     * Tanpa unique index, seeder pivot harus mengecek manual sebelum insert
     * karena tabel ini tidak punya constraint apa pun. Unique index memindahkan
     * aturan itu ke database, sehingga `insertOrIgnore()` cukup dan seeder
     * otomatis idempoten.
     */
    public function up(): void
    {
        $this->removeDuplicatePairs();

        Schema::table('products_trade_promo', function (Blueprint $table) {
            $table->unique(
                ['trade_promo_id', 'product_id'],
                'products_trade_promo_trade_promo_id_product_id_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('products_trade_promo', function (Blueprint $table) {
            $table->dropUnique('products_trade_promo_trade_promo_id_product_id_unique');
        });
    }

    /**
     * Buang duplikat, sisakan satu baris (paling lama) per pasangan.
     *
     * Wajib dijalankan sebelum unique index dipasang — MySQL menolak menambah
     * unique index pada kolom yang masih punya duplikat. `product_id` nullable,
     * jadi pencocokan NULL harus lewat `whereNull` (`where('col', null)` dalam
     * query builder menghasilkan `= NULL` yang tidak pernah cocok).
     */
    private function removeDuplicatePairs(): void
    {
        $duplicates = DB::table('products_trade_promo')
            ->select('trade_promo_id', 'product_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('trade_promo_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            $query = DB::table('products_trade_promo')
                ->where('trade_promo_id', $row->trade_promo_id)
                ->where('id', '<>', $row->keep_id);

            $row->product_id === null
                ? $query->whereNull('product_id')
                : $query->where('product_id', $row->product_id);

            $query->delete();
        }
    }
};
