<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promo tahap ketiga bisa berupa persentase ATAU nilai rupiah tetap.
     *
     * `percentage_1..3` hanya mewakili skema persentase; legacy memakai
     * `manual_type` ('PERCENTAGE' | 'VALUE') untuk memilih mana yang dipakai,
     * dengan `manual_percentage` / `manual_value` sebagai nilainya. Ketiga
     * kolom nullable karena baris promo lama tidak pernah mengisinya.
     */
    public function up(): void
    {
        if (Schema::hasColumn('assigned_products_promo', 'manual_type')) {
            return;
        }

        Schema::table('assigned_products_promo', function ($table) {
            $table->string('manual_type', 16)->nullable()->after('percentage_3');
            $table->decimal('manual_percentage', 5, 2)->nullable()->after('manual_type');
            $table->decimal('manual_value', 18, 2)->nullable()->after('manual_percentage');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('assigned_products_promo', 'manual_type')) {
            return;
        }

        Schema::table('assigned_products_promo', function ($table) {
            $table->dropColumn(['manual_type', 'manual_percentage', 'manual_value']);
        });
    }
};
