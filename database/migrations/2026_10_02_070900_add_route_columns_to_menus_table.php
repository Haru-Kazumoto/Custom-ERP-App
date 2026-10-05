<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sidebar menentukan menu aktif dari NAMA ROUTE, bukan dari kesamaan URL.
     * Contoh: `/purchase-orders/create` adalah saudara dari `/purchase-orders/documents`,
     * bukan anaknya, sehingga pencocokan prefiks URL tidak bisa memetakan halaman
     * turunan ke menu induknya. `route_name` menyimpan route utama yang diwakili menu,
     * `active_routes` menyimpan route turunan yang tetap menyalakan menu yang sama.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('route_name')->nullable()->after('url');
            $table->json('active_routes')->nullable()->after('route_name');
        });

        $backfill = [
            'dashboard' => ['route_name' => 'dashboard', 'active_routes' => null],
            // Menu grup struktural: url `/purchase-orders` tidak pernah jadi route,
            // status "menyala"-nya tetap turunan dari anak-anaknya.
            'purchase_order' => ['route_name' => null, 'active_routes' => null],
            'list_po_documents' => [
                'route_name' => 'purchase-order.index',
                'active_routes' => json_encode(['purchase-order.create', 'purchase-order.show']),
            ],
            'revision_po_documents' => ['route_name' => 'purchase-order.revisions', 'active_routes' => null],
            'sub_sales_order' => [
                'route_name' => 'sub-sales-order.index',
                'active_routes' => json_encode(['sub-sales-order.create']),
            ],
            // Route-nya belum terdaftar, biarkan kosong sampai modulnya dibuat.
            'travel_documents' => ['route_name' => null, 'active_routes' => null],
            'expedition' => ['route_name' => null, 'active_routes' => null],
            'warehouse_stock_products' => ['route_name' => null, 'active_routes' => null],
        ];

        foreach ($backfill as $key => $attributes) {
            DB::table('menus')->where('key', $key)->update($attributes);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['route_name', 'active_routes']);
        });
    }
};
