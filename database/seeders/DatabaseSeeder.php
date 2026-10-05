<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'username' => 'testuser',
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RoleSeeder::class,
            SubRoleSeeder::class,
            UserSeeder::class,
            // Tabel referensi produk. Harus jalan sebelum ProductSeeder karena
            // produknya menunjuk `product_type_id` / `product_sub_type_id`.
            ProductTypeSeeder::class,
            ProductSubTypeSeeder::class,
            // Vendor juga dirujuk `products.vendor_id`, jadi harus ada dulu.
            VendorSeeder::class,
            ProductSeeder::class,
            TradePromoSeeder::class,
            // Pivot promo–produk. Keduanya insertOrIgnore di unique index
            // `(trade_promo_id, product_id)`, jadi aman dijalankan berulang.
            ProductTradePromoSeeder::class,
            TradePromoProductSeeder::class,
        ]);
    }
}
