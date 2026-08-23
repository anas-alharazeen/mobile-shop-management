<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'أحمد محمد',
                'phone' => '0599123456',
                'whatsapp' => '0599123456',
                'email' => 'ahmed@example.com',
                'address' => 'رام الله - شارع القدس',
            ],
            [
                'name' => 'سارة أحمد',
                'phone' => '0599234567',
                'whatsapp' => '0599234567',
                'email' => 'sara@example.com',
                'address' => 'نابلس - دوار الساعة',
            ],
            [
                'name' => 'محمد خليل',
                'phone' => '0599345678',
                'whatsapp' => '0599345678',
                'email' => 'khalil@example.com',
                'address' => 'الخليل - شارع عين سارة',
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(
                ['phone' => $data['phone']],
                array_merge($data, ['code' => Customer::generateCode()])
            );
        }
    }
}
