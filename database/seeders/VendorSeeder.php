<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = [
            [
                'name' => 'PT Astra Komponen Indonesia',
                'code' => 'VND001',
                'legality' => 'PT',
                'taxpayer' => '01.234.567.8-901.000',
                'address' => 'Jl. Industri Raya No. 12, Bekasi',
            ],
            [
                'name' => 'PT Semesta Baja Tbk',
                'code' => 'VND002',
                'legality' => 'PT Tbk',
                'taxpayer' => '02.345.678.9-012.000',
                'address' => 'Jl. Gatot Subroto No. 88, Jakarta',
            ],
            [
                'name' => 'CV Sumber Makmur',
                'code' => 'VND003',
                'legality' => 'CV',
                'taxpayer' => '03.456.789.0-123.000',
                'address' => 'Jl. Ahmad Yani No. 45, Bandung',
            ],
            [
                'name' => 'PT Nusantara Logistik',
                'code' => 'VND004',
                'legality' => 'PT',
                'taxpayer' => '04.567.890.1-234.000',
                'address' => 'Jl. Soekarno Hatta No. 21, Surabaya',
            ],
            [
                'name' => 'PT Indo Teknik Persada',
                'code' => 'VND005',
                'legality' => 'PT',
                'taxpayer' => '05.678.901.2-345.000',
                'address' => 'Jl. Cikarang Industri Blok C2, Bekasi',
            ],
            [
                'name' => 'CV Maju Bersama',
                'code' => 'VND006',
                'legality' => 'CV',
                'taxpayer' => '06.789.012.3-456.000',
                'address' => 'Jl. Veteran No. 18, Semarang',
            ],
            [
                'name' => 'PT Global Elektrik Tbk',
                'code' => 'VND007',
                'legality' => 'PT Tbk',
                'taxpayer' => '07.890.123.4-567.000',
                'address' => 'Jl. Sudirman Kav. 52, Jakarta',
            ],
            [
                'name' => 'PT Mitra Karya Abadi',
                'code' => 'VND008',
                'legality' => 'PT',
                'taxpayer' => '08.901.234.5-678.000',
                'address' => 'Jl. Diponegoro No. 10, Yogyakarta',
            ],
            [
                'name' => 'CV Prima Sejahtera',
                'code' => 'VND009',
                'legality' => 'CV',
                'taxpayer' => '09.012.345.6-789.000',
                'address' => 'Jl. Imam Bonjol No. 31, Medan',
            ],
            [
                'name' => 'PT Cipta Sarana Industri',
                'code' => 'VND010',
                'legality' => 'PT',
                'taxpayer' => '10.123.456.7-890.000',
                'address' => 'Jl. Industri Barat No. 5, Karawang',
            ],
        ];

        collect($vendors)->each(function($data) {
            Vendor::create($data);
        });
    }
}
