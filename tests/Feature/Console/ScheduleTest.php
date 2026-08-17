<?php

use Illuminate\Console\Scheduling\Schedule as ConsoleSchedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

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
