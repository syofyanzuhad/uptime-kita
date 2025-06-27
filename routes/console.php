<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CalculateMonitorUptimeDailyJob;
use Spatie\UptimeMonitor\Commands\CheckUptime;
use Spatie\UptimeMonitor\Commands\CheckCertificates;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CheckUptime::class)->everyMinute();
Schedule::command(CheckCertificates::class)->daily();

// === LARAVEL HORIZON ===
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// === LARAVEL TELOSCOPE ===
Schedule::command('telescope:prune --hours=48')->everyFiveMinutes();

// === LARAVEL PRUNABLE MODELS ===
Schedule::command('model:prune')->daily();

Schedule::job(new CalculateMonitorUptimeDailyJob())->everyMinute();
// Schedule::job(new CalculateMonitorUptimeJob('WEEKLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('MONTHLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('YEARLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('ALL'))->hourly();
