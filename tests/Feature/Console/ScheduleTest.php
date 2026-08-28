<?php

use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Scheduling\Schedule as ConsoleSchedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;

/**
 * Rebuild the application schedule with the current config by re-running
 * routes/console.php against a fresh Schedule instance.
 */
function reloadApplicationSchedule(): ConsoleSchedule
{
    $schedule = app(ConsoleKernel::class)->resolveConsoleSchedule();

    app()->instance(ConsoleSchedule::class, $schedule);
    Facade::clearResolvedInstance(ConsoleSchedule::class);

    require base_path('routes/console.php');

    return $schedule;
}

function traceReplayPruneEvents(): Collection
{
    return collect(reloadApplicationSchedule()->events())
        ->filter(fn ($event) => $event->command !== null && str_contains($event->command, 'trace-replay:prune'));
}

it('schedules trace-replay:prune daily when trace-replay is enabled', function () {
    config(['trace-replay.enabled' => true]);

    $events = traceReplayPruneEvents();

    expect($events)->toHaveCount(1);
    expect($events->first()->command)->toContain('--days=30');
    expect($events->first()->expression)->toBe('0 0 * * *');
});

it('does not schedule trace-replay:prune when trace-replay is disabled', function () {
    config(['trace-replay.enabled' => false]);

    expect(traceReplayPruneEvents())->toHaveCount(0);
});

it('schedules monitor:check-uptime with custom cron expression when configured', function () {
    config([
        'uptime-monitor.schedule.cron' => '*/15 * * * *',
    ]);

    $schedule = reloadApplicationSchedule();
    $event = collect($schedule->events())
        ->first(fn ($e) => $e->command !== null && str_contains($e->command, 'monitor:check-uptime'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('*/15 * * * *');
});

it('schedules monitor:check-uptime hourly with custom minute offset', function () {
    config([
        'uptime-monitor.schedule.cron' => null,
        'uptime-monitor.schedule.frequency' => 'hourly',
        'uptime-monitor.schedule.minute' => 15,
    ]);

    $schedule = reloadApplicationSchedule();
    $event = collect($schedule->events())
        ->first(fn ($e) => $e->command !== null && str_contains($e->command, 'monitor:check-uptime'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('15 * * * *');
});

it('schedules monitor:check-uptime daily with custom time', function () {
    config([
        'uptime-monitor.schedule.cron' => null,
        'uptime-monitor.schedule.frequency' => 'daily',
        'uptime-monitor.schedule.time' => '04:30',
    ]);

    $schedule = reloadApplicationSchedule();
    $event = collect($schedule->events())
        ->first(fn ($e) => $e->command !== null && str_contains($e->command, 'monitor:check-uptime'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('30 4 * * *');
});

it('does not schedule monitor:check-uptime when frequency is none', function () {
    config([
        'uptime-monitor.schedule.cron' => null,
        'uptime-monitor.schedule.frequency' => 'none',
    ]);

    $schedule = reloadApplicationSchedule();
    $event = collect($schedule->events())
        ->first(fn ($e) => $e->command !== null && str_contains($e->command, 'monitor:check-uptime'));

    expect($event)->toBeNull();
});

it('configures heartbeat ping url for monitor:check-uptime schedule', function () {
    config([
        'uptime-monitor.schedule.uptime_check_heartbeat_url' => 'https://hc-ping.com/48755033-52c9-4470-a212-8acac2493f2f',
    ]);

    $schedule = reloadApplicationSchedule();
    $event = collect($schedule->events())
        ->first(fn ($e) => $e->command !== null && str_contains($e->command, 'monitor:check-uptime'));

    expect($event)->not->toBeNull();
    expect($event->runInBackground)->toBeFalse();
});

it('logs warning when monitor:check-uptime schedule is skipped due to overlap', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('UPTIME-CHECK: SKIPPED due to overlapping previous run');

    $event = collect(reloadApplicationSchedule()->events())
        ->first(fn ($e) => $e->command !== null && str_contains($e->command, 'monitor:check-uptime'));

    event(new ScheduledTaskSkipped($event));
});
