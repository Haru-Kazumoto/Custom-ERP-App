<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Penanda apakah satu jenis pengiriman punya sub-pengiriman.
     *
     * DEPO, DIRECT, dan DIRECT_DEPO punya sub (minimal satu placeholder),
     * sementara DO tidak — jadi form Delivery Order harus menyembunyikan
     * select sub. Tanpa kolom ini frontend harus menebak dari jumlah baris
     * `sub_shippings`, yang berarti satu request tambahan tiap kali jenis
     * pengiriman diganti.
     */
    public function up(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('shippings', 'has_sub')) {
            return;
        }

        DB::getSchemaBuilder()->table('shippings', function ($table) {
            $table->boolean('has_sub')->default(false)->after('code');
        });
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('shippings', 'has_sub')) {
            DB::getSchemaBuilder()->table('shippings', function ($table) {
                $table->dropColumn('has_sub');
            });
        }
    }
};
