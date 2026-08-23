<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// نسخة يومية من قاعدة البيانات وتنظيف النسخ القديمة.
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();
