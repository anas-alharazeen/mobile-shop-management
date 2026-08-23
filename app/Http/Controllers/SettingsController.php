<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\FinancialAccount;
use App\Services\BackupService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly BackupService $backups
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'store' => $this->settings->getStoreSettings(),
            'invoice' => $this->settings->getInvoiceSettings(),
            'payment' => $this->settings->getPaymentSettings(),
            'financialAccounts' => FinancialAccount::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'system' => [
                'app_name' => config('app.name'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'app_timezone' => config('app.timezone'),
                'app_locale' => config('app.locale'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'zip_available' => class_exists(\ZipArchive::class),
            ],
            'backup' => [
                'last_backup' => $this->backups->getLastBackupInfo(),
                'backup_count' => $this->backups->getBackupCount(),
                'backups' => $this->backups->getBackups(),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $validated = $request->validated();
        $oldLogo = $this->settings->get('store_logo');

        if ($request->hasFile('store_logo')) {
            $validated['store_logo'] = $request->file('store_logo')->store('settings', 'public');
        } elseif ($request->boolean('remove_store_logo')) {
            $validated['store_logo'] = null;
        }

        $this->settings->update($validated);

        if ($oldLogo && $oldLogo !== ($validated['store_logo'] ?? $oldLogo) && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        return to_route('settings.index')->with('success', 'تم حفظ الإعدادات بنجاح.');
    }

    public function createBackup()
    {
        $result = $this->backups->create();

        return to_route('settings.index')->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function downloadBackup(string $filename): StreamedResponse
    {
        return $this->backups->download($filename);
    }

    public function deleteBackup(string $filename)
    {
        $result = $this->backups->delete($filename);

        return to_route('settings.index')->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function cleanBackups()
    {
        $result = $this->backups->clean();

        return to_route('settings.index')->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function getBackupStatus(): JsonResponse
    {
        try {
            $backups = $this->backups->getBackups();

            return response()->json([
                'backups' => $backups,
                'last_backup' => $backups[0] ?? null,
                'count' => count($backups),
                'zip_available' => class_exists(\ZipArchive::class),
            ]);
        } catch (\Throwable $exception) {
            Log::error('تعذر قراءة حالة النسخ الاحتياطي', ['exception' => $exception]);

            return response()->json(['message' => 'تعذر قراءة النسخ الاحتياطية.'], 500);
        }
    }
}
