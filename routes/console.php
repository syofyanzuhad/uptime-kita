<?php

use App\Console\Commands\CheckDomainExpiration;
use App\Jobs\CalculateMonitorStatisticsJob;
use App\Jobs\SendBatchedNotificationsJob;
use App\Jobs\SendTelemetryPingJob;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\UptimeMonitor\Commands\CheckCertificates;

$scheduleFrequency = config('uptime-monitor.schedule.frequency', 'everyMinute');
$scheduleCron = config('uptime-monitor.schedule.cron');
$scheduleMinute = (int) config('uptime-monitor.schedule.minute', 0);
$scheduleTime = config('uptime-monitor.schedule.time', '00:00');

if ($scheduleFrequency !== 'none') {
    $applySchedule = function ($event, int $minuteOffset = 0) use ($scheduleFrequency, $scheduleCron, $scheduleMinute, $scheduleTime) {
        if (! empty($scheduleCron)) {
            return $event->cron($scheduleCron);
        }

        if ($scheduleFrequency === 'hourly') {
            $minute = ($scheduleMinute + $minuteOffset) % 60;

            return $event->hourlyAt($minute);
        }

        if ($scheduleFrequency === 'daily') {
            return $event->dailyAt($scheduleTime);
        }

        if (method_exists($event, $scheduleFrequency)) {
            return $event->$scheduleFrequency();
        }

        return $event->everyMinute();
    };

    // Main uptime check - runs according to SCHEDULE_FREQUENCY (e.g. everyMinute)
    $uptimeCheckEvent = Schedule::command('monitor:check-uptime')
        ->withoutOverlapping(2)
        ->before(function () {
            info('UPTIME-CHECK: STARTED');
        })
        ->onSuccess(function () {
            info('UPTIME-CHECK: SUCCESS');
        })
        ->onFailure(function () {
            info('UPTIME-CHECK: FAILED');
        });

    $heartbeatUrl = config('uptime-monitor.schedule.uptime_check_heartbeat_url');
    if (! empty($heartbeatUrl)) {
        $uptimeCheckEvent
            ->pingBefore($heartbeatUrl.'/start')
            ->pingOnSuccess($heartbeatUrl)
            ->pingOnFailure($heartbeatUrl.'/fail');
    }

    $applySchedule($uptimeCheckEvent, 0);

    // Maintenance windows update & batched notifications
    $applySchedule(Schedule::command('monitor:update-maintenance-status'), 10);
    $applySchedule(Schedule::job(new SendBatchedNotificationsJob), 5);

    // Heavy aggregation job: calculate stats every 15 minutes
    Schedule::job(new CalculateMonitorStatisticsJob)
        ->everySixHours(minutes: 5)
        ->withoutOverlapping(10);
}

Schedule::command(CheckCertificates::class)->twiceDailyAt(1, 13, 15);
Schedule::command(CheckDomainExpiration::class)->twiceDailyAt(1, 13, 15);
Schedule::command('uptime:calculate-daily')->everyThreeHours(5);
Schedule::command('monitor:update-maintenance-status --cleanup')->everySixHours(10);

// === LARAVEL HORIZON ===
if (config('queue.default') === 'redis') {
    Schedule::command('horizon:snapshot')->everyFiveMinutes();
    Schedule::command('horizon:forget --all')->daily();
}
Schedule::command('queue:prune-batches')->daily();

// === LARAVEL TELESCOPE ===
if (config('telescope.enabled')) {
    Schedule::command('telescope:prune --hours=48')->everyOddHour();
}

// === TRACE-REPLAY ===
if (config('trace-replay.enabled')) {
    Schedule::command('trace-replay:prune --days=30')->daily();
}

// === LARAVEL PRUNABLE MODELS ===
Schedule::command('model:prune')->daily();
Schedule::command('model:prune', ['--model' => [HealthCheckResultHistoryItem::class]])->daily();

Schedule::command('sitemap:generate')->daily();

if (config('database.default') === 'sqlite') {
    Schedule::command('sqlite:optimize')->weeklyOn(0, '2:00');
}

// === ANONYMOUS TELEMETRY ===
if (config('telemetry.enabled')) {
    $frequency = config('telemetry.frequency', 'daily');

    $telemetrySchedule = Schedule::job(new SendTelemetryPingJob);

    match ($frequency) {
        'hourly' => $telemetrySchedule->hourly(),
        'weekly' => $telemetrySchedule->weekly(),
        default => $telemetrySchedule->daily(),
    };
}

// === BACKUP DB ===
if (config('backup.enabled', true)) {
    Schedule::command('backup:clean')->daily()->at('01:00');
    Schedule::command('backup:run')->daily()->at('01:30')
        ->onSuccess(function () {
            info('BACKUP-DB: SUCCESS');
        })
        ->onFailure(function () {
            info('BACKUP-DB: FAILED');
        });
}
