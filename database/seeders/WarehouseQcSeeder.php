<?php

namespace Database\Seeders;

use App\Models\Warehouse\Warehouse;
use App\Models\Warehouse\Location;
use Illuminate\Database\Seeder;

class WarehouseQcSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bikin Gudang QC (tambahkan kode)
        $whQc = Warehouse::firstOrCreate(
            ['kode' => 'WH-QC'], // <-- Tambahkan ini
            [
                'nama' => 'Gudang Quality Control & Reject',
                'is_active' => true
            ]
        );

        // 2. Bikin Lokasi Karantina
        Location::firstOrCreate(
            ['kode' => 'QC-KARANTINA'],
            [
                'warehouse_id' => $whQc->id,
                'nama'         => 'Area Karantina (Pending IQC)',
                'is_active'    => true
            ]
        );

        // 3. Bikin Lokasi Reject
        Location::firstOrCreate(
            ['kode' => 'QC-REJECT'],
            [
                'warehouse_id' => $whQc->id,
                'nama'         => 'Area Barang Reject',
                'is_active'    => true
            ]
        );
    }
}