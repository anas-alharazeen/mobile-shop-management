<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // بيانات المعرض
            ['key' => 'store_name', 'value' => 'فنانة فون', 'type' => 'string', 'group' => 'store'],
            ['key' => 'store_phone', 'value' => '0599-123456', 'type' => 'string', 'group' => 'store'],
            ['key' => 'store_whatsapp', 'value' => '0599-123456', 'type' => 'string', 'group' => 'store'],
            ['key' => 'store_email', 'value' => 'info@fanana-phone.local', 'type' => 'string', 'group' => 'store'],
            ['key' => 'store_address', 'value' => '', 'type' => 'string', 'group' => 'store'],
            ['key' => 'footer_text', 'value' => 'شكراً لثقتكم بفنانة فون', 'type' => 'string', 'group' => 'store'],

            // الفواتير
            ['key' => 'invoice_footer', 'value' => '', 'type' => 'string', 'group' => 'invoice'],
            ['key' => 'return_policy', 'value' => 'المنتج المستخدم غير قابل للاستبدال', 'type' => 'string', 'group' => 'invoice'],
            ['key' => 'sales_notes', 'value' => '', 'type' => 'string', 'group' => 'invoice'],
            ['key' => 'repair_notes', 'value' => '', 'type' => 'string', 'group' => 'invoice'],
            ['key' => 'default_low_stock', 'value' => '5', 'type' => 'integer', 'group' => 'stock'],

            // الدفع
            ['key' => 'bank_name', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'bank_account', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'bank_iban', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'app_name', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'app_account', 'value' => '', 'type' => 'string', 'group' => 'payment'],
            ['key' => 'payment_methods', 'value' => ['cash', 'bank_transfer', 'banking_app'], 'type' => 'json', 'group' => 'payment'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
