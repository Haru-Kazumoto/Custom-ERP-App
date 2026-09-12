<?php

namespace Database\Seeders;

use App\Models\TradePromo;
use Illuminate\Database\Seeder;

class TradePromoSeeder extends Seeder
{
    public function run(): void
    {
        $promos = [
            ['name' => 'Opening Discount', 'price' => 5000, 'quota' => 100, 'is_active' => true],
            ['name' => 'Member Special', 'price' => 7500, 'quota' => 150, 'is_active' => true],
            ['name' => 'Bulk Purchase Promo', 'price' => 10000, 'quota' => 80, 'is_active' => true],
            ['name' => 'Distributor Cashback', 'price' => 15000, 'quota' => 60, 'is_active' => true],
            ['name' => 'Ramadhan Promo', 'price' => 12000, 'quota' => 200, 'is_active' => true],
            ['name' => 'Lebaran Promo', 'price' => 20000, 'quota' => 120, 'is_active' => true],
            ['name' => 'End of Month Promo', 'price' => 8000, 'quota' => 90, 'is_active' => true],
            ['name' => 'Weekend Sale', 'price' => 6000, 'quota' => 75, 'is_active' => true],
            ['name' => 'Flash Discount', 'price' => 9000, 'quota' => 50, 'is_active' => true],
            ['name' => 'Loyal Customer', 'price' => 11000, 'quota' => 130, 'is_active' => true],
            ['name' => 'Wholesale Promo', 'price' => 18000, 'quota' => 40, 'is_active' => true],
            ['name' => 'New Product Promo', 'price' => 7000, 'quota' => 100, 'is_active' => true],
            ['name' => 'Buy More Save More', 'price' => 13000, 'quota' => 70, 'is_active' => true],
            ['name' => 'Special Event', 'price' => 9500, 'quota' => 90, 'is_active' => true],
            ['name' => 'Christmas Promo', 'price' => 17000, 'quota' => 120, 'is_active' => false],
            ['name' => 'New Year Promo', 'price' => 16000, 'quota' => 150, 'is_active' => false],
            ['name' => 'Clearance Promo', 'price' => 25000, 'quota' => 30, 'is_active' => true],
            ['name' => 'National Day Promo', 'price' => 10000, 'quota' => 110, 'is_active' => true],
            ['name' => 'Super Saving Promo', 'price' => 14000, 'quota' => 85, 'is_active' => true],
            ['name' => 'Factory Direct Promo', 'price' => 19000, 'quota' => 45, 'is_active' => true],
        ];

        foreach ($promos as $promo) {
            TradePromo::create($promo);
        }
    }
}