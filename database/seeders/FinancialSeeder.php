<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء حساب الكاش الافتراضي
        $cashAccount = FinancialAccount::updateOrCreate(
            ['name' => 'صندوق الكاش'],
            [
                'type' => AccountType::CASH,
                'opening_balance' => 0,
                'current_balance' => 0,
                'description' => 'الصندوق الرئيسي للمعرض',
                'is_active' => true,
            ]
        );

        Setting::set('default_cash_account', $cashAccount->id, 'integer', 'payment');

        // إنشاء تصنيفات المصروفات
        $categories = [
            ['name' => 'إيجار المعرض', 'description' => 'إيجار المحل التجاري'],
            ['name' => 'الكهرباء', 'description' => 'فواتير الكهرباء'],
            ['name' => 'الإنترنت', 'description' => 'اشتراك الإنترنت والاتصالات'],
            ['name' => 'الرواتب', 'description' => 'رواتب الموظفين'],
            ['name' => 'الشحن والنقل', 'description' => 'تكاليف الشحن والنقل'],
            ['name' => 'التسويق والإعلانات', 'description' => 'مصاريف التسويق والإعلان'],
            ['name' => 'الأدوات التشغيلية', 'description' => 'أدوات ومستلزمات التشغيل'],
            ['name' => 'صيانة معدات المعرض', 'description' => 'صيانة أجهزة وأثاث المعرض'],
            ['name' => 'ضيافة', 'description' => 'مصاريف الضيافة للعملاء'],
            ['name' => 'مصروفات أخرى', 'description' => 'مصروفات غير مصنفة'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
