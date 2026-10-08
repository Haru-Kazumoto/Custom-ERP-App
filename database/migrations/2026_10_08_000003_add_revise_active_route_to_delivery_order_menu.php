<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menu "Revisi DO" (`delivery-order-revisions`) belum punya route turunan
     * untuk formnya: `/delivery-order/{id}/revise`.
     *
     * Tanpa `delivery-order.revise` di `active_routes`, `resolveActiveMenuKey()`
     * tidak menemukan leaf yang cocok saat form revisi terbuka — sidebar hanya
     * menyala di grup "Delivery Order" induknya, dan user kehilangan posisi
     * menu yang ia buka. Pola yang sama dengan migration
     * `2026_10_05_040000_add_revision_active_route_to_menu` (Revisi PO).
     *
     * `delivery-order.show` dan `delivery-order.edit` lama dibiarkan: show
     * dipakai saat kembali dari form ke detail, dan edit tidak merusak apa
     * pun walaupun route-nya belum ada.
     */
    public function up(): void
    {
        DB::table('menus')
            ->where('key', 'delivery-order-revisions')
            ->update([
                'active_routes' => json_encode([
                    'delivery-order.show',
                    'delivery-order.edit',
                    'delivery-order.revise',
                ]),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('menus')
            ->where('key', 'delivery-order-revisions')
            ->update([
                'active_routes' => json_encode([
                    'delivery-order.show',
                    'delivery-order.edit',
                ]),
                'updated_at' => now(),
            ]);
    }
};
