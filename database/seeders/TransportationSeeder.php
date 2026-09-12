<?php

namespace Database\Seeders;

use App\Models\Transportation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransportationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transportations = [
            [
                'name' => 'Truck Box',
                'code' => 'TRP001',
                'description' => 'Closed box truck for general cargo delivery.',
            ],
            [
                'name' => 'Pickup',
                'code' => 'TRP002',
                'description' => 'Light-duty vehicle for small quantity deliveries.',
            ],
            [
                'name' => 'Van',
                'code' => 'TRP003',
                'description' => 'Suitable for medium-sized goods and city distribution.',
            ],
            [
                'name' => 'Trailer',
                'code' => 'TRP004',
                'description' => 'Heavy-duty transportation for large shipments.',
            ],
            [
                'name' => 'Container Truck',
                'code' => 'TRP005',
                'description' => 'Transportation for containerized cargo.',
            ],
            [
                'name' => 'Wing Box Truck',
                'code' => 'TRP006',
                'description' => 'Truck with side-opening cargo area for easier loading.',
            ],
            [
                'name' => 'Flatbed Truck',
                'code' => 'TRP007',
                'description' => 'Open platform truck for oversized cargo.',
            ],
            [
                'name' => 'Courier Service',
                'code' => 'TRP008',
                'description' => 'Third-party courier for parcel and document delivery.',
            ],
            [
                'name' => 'Cargo Ship',
                'code' => 'TRP009',
                'description' => 'Sea freight transportation for bulk shipments.',
            ],
            [
                'name' => 'Air Freight',
                'code' => 'TRP010',
                'description' => 'Air transportation for urgent and high-value goods.',
            ],
        ];

        collect($transportations)->each(function($data) {
            Transportation::create($data);
        });
    }
}
