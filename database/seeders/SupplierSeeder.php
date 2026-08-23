<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'محمد العلي',
                'company_name' => 'شركة العلي للتوزيع',
                'phone' => '0599123456',
                'whatsapp' => '0599123456',
                'email' => 'ali@example.com',
                'address' => 'رام الله - شارع القدس',
            ],
            [
                'name' => 'أحمد سمير',
                'company_name' => 'مؤسسة سمير للإلكترونيات',
                'phone' => '0599234567',
                'whatsapp' => '0599234567',
                'email' => 'samir@example.com',
                'address' => 'نابلس - دوار الساعة',
            ],
            [
                'name' => 'سارة خليل',
                'company_name' => 'شركة خليل للهواتف',
                'phone' => '0599345678',
                'whatsapp' => '0599345678',
                'email' => 'khalil@example.com',
                'address' => 'الخليل - شارع عين سارة',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(
                ['phone' => $data['phone']],
                array_merge($data, ['code' => Supplier::generateCode()])
            );
        }
    }
}
