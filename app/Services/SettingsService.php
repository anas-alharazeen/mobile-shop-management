<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected $cacheKey = 'app_settings';
    protected $cacheTTL = 3600; // 1 ساعة

    /**
     * الحصول على جميع الإعدادات مع Cache
     */
    public function all()
    {
        return Cache::remember($this->cacheKey, $this->cacheTTL, function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * الحصول على إعداد معين
     */
    public function get($key, $default = null)
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    /**
     * تعيين إعداد
     */
    public function set($key, $value, $type = 'string', $group = 'general')
    {
        $setting = Setting::set($key, $value, $type, $group);
        $this->clearCache();
        return $setting;
    }

    /**
     * تحديث إعدادات متعددة
     */
    public function update(array $data): bool
    {
        foreach ($data as $key => $value) {
            $existing = Setting::query()->where('key', $key)->first();
            Setting::set(
                $key,
                $value,
                $existing?->type ?? $this->inferType($value),
                $existing?->group ?? 'general'
            );
        }

        $this->clearCache();

        return true;
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_array($value) => 'json',
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            default => 'string',
        };
    }

    /**
     * مسح الكاش
     */
    public function clearCache()
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * الحصول على إعدادات المعرض
     */
    public function getStoreSettings()
    {
        return [
            'store_name' => $this->get('store_name', 'فنانة فون'),
            'store_phone' => $this->get('store_phone', '0599-123456'),
            'store_whatsapp' => $this->get('store_whatsapp', '0599-123456'),
            'store_email' => $this->get('store_email', 'info@fanana-phone.local'),
            'store_address' => $this->get('store_address', ''),
            'store_logo' => $this->get('store_logo', null),
            'footer_text' => $this->get('footer_text', 'شكراً لثقتكم بفنانة فون'),
        ];
    }

    /**
     * الحصول على إعدادات الفواتير
     */
    public function getInvoiceSettings()
    {
        return [
            'invoice_footer' => $this->get('invoice_footer', ''),
            'return_policy' => $this->get('return_policy', ''),
            'sales_notes' => $this->get('sales_notes', ''),
            'repair_notes' => $this->get('repair_notes', ''),
            'default_low_stock' => $this->get('default_low_stock', 5),
        ];
    }

    /**
     * الحصول على إعدادات الدفع
     */
    public function getPaymentSettings()
    {
        return [
            'bank_name' => $this->get('bank_name', ''),
            'bank_account' => $this->get('bank_account', ''),
            'bank_iban' => $this->get('bank_iban', ''),
            'app_name' => $this->get('app_name', ''),
            'app_account' => $this->get('app_account', ''),
            'default_cash_account' => $this->get('default_cash_account', null),
            'payment_methods' => $this->get('payment_methods', ['cash', 'bank_transfer', 'banking_app']),
        ];
    }
}
