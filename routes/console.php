<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly integrity + reconciliation sweep. Failures are written to the app log
// and echoed into storage/logs/nightly-check.log by the scheduler.
Schedule::command('system:nightly')
    ->dailyAt('23:30')
    ->withoutOverlapping(10)
    ->appendOutputTo(storage_path('logs/nightly-check.log'));

// Database dump after the check, keeping 30 days of copies.
Schedule::command('system:backup --keep=30')
    ->dailyAt('23:45')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/nightly-check.log'));
