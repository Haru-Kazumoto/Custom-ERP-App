<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menu "Revisi PO" (`revision_po_documents`) sebelumnya `active_routes` NULL,
     * padahal sekarang ada halaman turunan: `/purchase-orders/{id}/revise`.
     *
     * Tanpa `active_routes`, `resolveActiveMenuKey()` tidak akan menemukan induk
     * yang cocok saat form revisi terbuka dan sidebar ikut terkilir ke menu
     * lain, padahal user masih berada di subtree "Purchase Order".
     */
    public function up(): void
    {
        DB::table('menus')
            ->where('key', 'revision_po_documents')
            ->update([
                'active_routes' => json_encode(['purchase-order.revise']),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('menus')
            ->where('key', 'revision_po_documents')
            ->update([
                'active_routes' => null,
                'updated_at' => now(),
            ]);
    }
};
