<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\SettingsService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // تسجيل SettingsService كـ Singleton
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تعيين الحد الأقصى لطول المفتاح في قاعدة البيانات
        Schema::defaultStringLength(191);

        // Prefetch Vite assets
        Vite::prefetch(concurrency: 3);

        // تسجيل الاستعلامات البطيئة في بيئة التطوير
        if (config('app.env') === 'local') {
            DB::listen(function ($query) {
                if ($query->time > 500) { // أكثر من 500 مللي ثانية
                    Log::warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }

        // تعطيل التسجيل العام بشكل نهائي
        // هذا يضمن عدم وجود أي Route للتسجيل
        // يتم التحكم به أيضاً في routes/web.php

        // تطبيق الإعدادات الديناميكية على Config
        $this->applyDynamicSettings();
    }

    /**
     * تطبيق الإعدادات الديناميكية من قاعدة البيانات
     */
    protected function applyDynamicSettings(): void
    {
        try {
            // التحقق من وجود جدول الإعدادات
            if (app()->runningInConsole() || !Schema::hasTable('settings')) {
                return;
            }

            // جلب الإعدادات من Cache أو قاعدة البيانات
            $settings = Cache::remember('app_settings_dynamic', 3600, function () {
                return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            });

            // تطبيق الإعدادات على Config
            if (isset($settings['store_name'])) {
                config(['app.name' => $settings['store_name']]);
            }

            if (isset($settings['store_phone'])) {
                config(['app.phone' => $settings['store_phone']]);
            }

            if (isset($settings['store_email'])) {
                config(['app.email' => $settings['store_email']]);
            }

            if (isset($settings['store_address'])) {
                config(['app.address' => $settings['store_address']]);
            }

        } catch (\Exception $e) {
            // تجاهل الأخطاء في حالة عدم وجود جدول الإعدادات
            // أو في بيئة التطوير (لا تؤثر على التشغيل)
            if (config('app.env') === 'local') {
                Log::debug('Settings table not found or error loading settings: ' . $e->getMessage());
            }
        }
    }
}
