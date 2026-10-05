<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Kode produk dipakai sebagai identitas bisnis (label di dokumen PO,
            // surat jalan, promo), jadi duplikat harus dicegah di level DB —
            // validasi form saja tidak cukup karena bisa dilewati import/API.
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
};
