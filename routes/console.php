<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\UptimeMonitor\Commands\CheckUptime;
use Spatie\UptimeMonitor\Commands\CheckCertificates;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckUptime::class)->everyMinute();
Schedule::command(CheckCertificates::class)->everyMinute();

// === LARAVEL HORIZON ===
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// === LARAVEL TELOSCOPE ===
Schedule::command('telescope:prune --hours=48')->everyFiveMinutes();