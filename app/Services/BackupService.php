<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupService
{
    public function create(): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [
                'success' => false,
                'message' => 'امتداد PHP Zip غير مفعّل. فعّل ext-zip ثم أعد تشغيل الخادم.',
            ];
        }

        try {
            Artisan::call('backup:run');
            $output = trim(Artisan::output());
            Log::info('تم إنشاء نسخة احتياطية', ['output' => $output]);

            return [
                'success' => true,
                'message' => 'تم إنشاء النسخة الاحتياطية بنجاح.',
                'output' => $output,
            ];
        } catch (\Throwable $exception) {
            Log::error('فشل إنشاء النسخة الاحتياطية', ['exception' => $exception]);

            return [
                'success' => false,
                'message' => 'فشل إنشاء النسخة الاحتياطية. راجع سجل النظام وإعدادات قاعدة البيانات.',
            ];
        }
    }

    public function getBackups(): array
    {
        $disk = Storage::disk('local');
        $directory = $this->backupDirectory();
        if (!$disk->exists($directory)) {
            return [];
        }

        $backups = [];
        foreach ($disk->files($directory) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'zip') {
                continue;
            }

            $modifiedAt = $disk->lastModified($file);
            $size = $disk->size($file);
            $backups[] = [
                'name' => basename($file),
                'size' => $size,
                'size_formatted' => $this->formatSize($size),
                'modified_at' => $modifiedAt,
                'modified_at_formatted' => date('Y-m-d H:i:s', $modifiedAt),
            ];
        }

        usort($backups, fn (array $a, array $b) => $b['modified_at'] <=> $a['modified_at']);

        return $backups;
    }

    public function download(string $filename): StreamedResponse
    {
        $path = $this->safeBackupPath($filename);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, basename($path), ['Content-Type' => 'application/zip']);
    }

    public function delete(string $filename): array
    {
        $path = $this->safeBackupPath($filename);
        $disk = Storage::disk('local');
        if (!$disk->exists($path)) {
            return ['success' => false, 'message' => 'ملف النسخة الاحتياطية غير موجود.'];
        }

        $disk->delete($path);
        Log::info('تم حذف نسخة احتياطية', ['file' => basename($path)]);

        return ['success' => true, 'message' => 'تم حذف النسخة الاحتياطية بنجاح.'];
    }

    public function clean(): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return ['success' => false, 'message' => 'امتداد PHP Zip غير مفعّل.'];
        }

        try {
            Artisan::call('backup:clean');

            return ['success' => true, 'message' => 'تم تنظيف النسخ القديمة بنجاح.'];
        } catch (\Throwable $exception) {
            Log::error('فشل تنظيف النسخ الاحتياطية', ['exception' => $exception]);

            return ['success' => false, 'message' => 'تعذر تنظيف النسخ الاحتياطية.'];
        }
    }

    public function getLastBackupInfo(): ?array
    {
        return $this->getBackups()[0] ?? null;
    }

    public function getBackupCount(): int
    {
        return count($this->getBackups());
    }

    private function backupDirectory(): string
    {
        return trim((string) config('backup.backup.name', config('app.name', 'fanana-phone')), '/');
    }

    private function safeBackupPath(string $filename): string
    {
        $safeName = basename($filename);
        if ($safeName !== $filename || strtolower(pathinfo($safeName, PATHINFO_EXTENSION)) !== 'zip') {
            abort(404);
        }

        return $this->backupDirectory() . '/' . $safeName;
    }

    private function formatSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1073741824 => number_format($bytes / 1073741824, 2) . ' GB',
            $bytes >= 1048576 => number_format($bytes / 1048576, 2) . ' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 2) . ' KB',
            default => $bytes . ' B',
        };
    }
}
