<?php

namespace Database\Seeders;

use App\Enums\WarehouseType;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['name' => 'مخزون المبيعات', 'type' => WarehouseType::SALES],
            ['name' => 'مخزون الصيانة', 'type' => WarehouseType::MAINTENANCE],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['name' => $warehouse['name']],
                [
                    'type' => $warehouse['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
