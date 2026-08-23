<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app' => fn () => $this->applicationSettings(),
        ];
    }
    /**
     * الإعدادات البسيطة المشتركة مع جميع صفحات Inertia.
     */
    private function applicationSettings(): array
    {
        try {
            $settings = app(\App\Services\SettingsService::class)->getStoreSettings();

            return [
                'name' => $settings['store_name'] ?? config('app.name'),
                'logo' => $settings['store_logo'] ?? null,
                'phone' => $settings['store_phone'] ?? null,
            ];
        } catch (\Throwable) {
            return [
                'name' => config('app.name', 'فنانة فون'),
                'logo' => null,
                'phone' => null,
            ];
        }
    }

}
