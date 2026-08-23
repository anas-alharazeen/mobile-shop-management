<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'الهواتف الذكية', 'type' => CategoryType::PHONE],
            ['name' => 'الأجهزة اللوحية', 'type' => CategoryType::TABLET],
            ['name' => 'الشواحن', 'type' => CategoryType::ACCESSORY],
            ['name' => 'الكوابل', 'type' => CategoryType::ACCESSORY],
            ['name' => 'السماعات', 'type' => CategoryType::ACCESSORY],
            ['name' => 'حافظات الهواتف', 'type' => CategoryType::ACCESSORY],
            ['name' => 'شاشات الحماية', 'type' => CategoryType::ACCESSORY],
            ['name' => 'البطاريات', 'type' => CategoryType::SPARE_PART],
            ['name' => 'شاشات الصيانة', 'type' => CategoryType::SPARE_PART],
            ['name' => 'مقابس الشحن', 'type' => CategoryType::SPARE_PART],
            ['name' => 'أدوات الصيانة', 'type' => CategoryType::MAINTENANCE_TOOL],
            ['name' => 'مواد الصيانة', 'type' => CategoryType::MAINTENANCE_MATERIAL],
            ['name' => 'منتجات أخرى', 'type' => CategoryType::OTHER],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'type' => $category['type'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
