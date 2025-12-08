<?php

use App\Jobs\ConfirmMonitorDowntimeJob;
use App\Listeners\DispatchConfirmationCheck;
use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Helpers\Period;

beforeEach(function () {
    // Disable global scopes for testing
    Monitor::withoutGlobalScopes();
});

it('dispatches confirmation check job on first failure', function () {
    Queue::fake();

    config(['uptime-monitor.confirmation_check.enabled' => true]);
    config(['uptime-monitor.confirmation_check.delay_seconds' => 30]);

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'down',
        'uptime_check_times_failed_in_a_row' => 1,
        'uptime_check_failure_reason' => 'Connection timeout',
        'uptime_status_last_change_date' => now()->subMinutes(5),
    ]);

    $downtimePeriod = new Period(now()->subMinutes(5), now());
    $event = new UptimeCheckFailed($monitor, $downtimePeriod);
    $listener = new DispatchConfirmationCheck;

    $result = $listener->handle($event);

    expect($result)->toBeFalse(); // Should stop propagation

    Queue::assertPushed(ConfirmMonitorDowntimeJob::class, function ($job) use ($monitor) {
        return $job->monitorId === $monitor->id
            && $job->failureReason === 'Connection timeout';
    });
});

it('does not dispatch confirmation check when disabled', function () {
    Queue::fake();

    config(['uptime-monitor.confirmation_check.enabled' => false]);

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'down',
        'uptime_check_times_failed_in_a_row' => 1,
        'uptime_status_last_change_date' => now()->subMinutes(5),
    ]);

    $downtimePeriod = new Period(now()->subMinutes(5), now());
    $event = new UptimeCheckFailed($monitor, $downtimePeriod);
    $listener = new DispatchConfirmationCheck;

    $result = $listener->handle($event);

    expect($result)->toBeTrue(); // Should let event propagate

    Queue::assertNotPushed(ConfirmMonitorDowntimeJob::class);
});

it('lets event propagate on subsequent failures', function () {
    Queue::fake();

    config(['uptime-monitor.confirmation_check.enabled' => true]);

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'down',
        'uptime_check_times_failed_in_a_row' => 2, // Not first failure
        'uptime_status_last_change_date' => now()->subMinutes(10),
    ]);

    $downtimePeriod = new Period(now()->subMinutes(10), now());
    $event = new UptimeCheckFailed($monitor, $downtimePeriod);
    $listener = new DispatchConfirmationCheck;

    $result = $listener->handle($event);

    expect($result)->toBeTrue(); // Should let event propagate

    Queue::assertNotPushed(ConfirmMonitorDowntimeJob::class);
});

it('handles monitor that is still down', function () {
    Event::fake([UptimeCheckFailed::class]);

    // Create a monitor that will fail the confirmation check (unreachable URL)
    $monitor = Monitor::factory()->create([
        'url' => 'https://this-url-definitely-does-not-exist-12345.invalid',
        'uptime_status' => 'down',
        'uptime_check_times_failed_in_a_row' => 1,
        'uptime_check_failure_reason' => 'Connection timeout',
        'uptime_status_last_change_date' => now()->subMinutes(5),
        'transient_failures_count' => 0,
    ]);

    $job = new ConfirmMonitorDowntimeJob(
        $monitor->id,
        'Connection timeout',
        1
    );

    $job->handle();

    // Should fire UptimeCheckFailed event since the URL is unreachable
    Event::assertDispatched(UptimeCheckFailed::class);
})->skip('Skipping test that requires actual HTTP request to unreachable URL');

it('skips confirmation if monitor already recovered', function () {
    Event::fake([UptimeCheckFailed::class]);

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'up', // Already recovered
        'uptime_check_times_failed_in_a_row' => 0,
        'uptime_status_last_change_date' => now(),
    ]);

    $job = new ConfirmMonitorDowntimeJob(
        $monitor->id,
        'Connection timeout',
        1
    );

    $job->handle();

    Event::assertNotDispatched(UptimeCheckFailed::class);
});

it('skips confirmation if monitor is disabled', function () {
    Event::fake([UptimeCheckFailed::class]);

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => false,
        'uptime_status' => 'down',
        'uptime_status_last_change_date' => now()->subMinutes(5),
    ]);

    $job = new ConfirmMonitorDowntimeJob(
        $monitor->id,
        'Connection timeout',
        1
    );

    $job->handle();

    Event::assertNotDispatched(UptimeCheckFailed::class);
});

it('skips confirmation if monitor not found', function () {
    Event::fake([UptimeCheckFailed::class]);

    $job = new ConfirmMonitorDowntimeJob(
        99999, // Non-existent monitor ID
        'Connection timeout',
        1
    );

    $job->handle();

    Event::assertNotDispatched(UptimeCheckFailed::class);
});

it('logs transient failure when already recovered', function () {
    Carbon::setTestNow(now());

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'up', // Already recovered
        'uptime_check_times_failed_in_a_row' => 0,
        'transient_failures_count' => 5,
        'uptime_status_last_change_date' => now(),
    ]);

    $job = new ConfirmMonitorDowntimeJob(
        $monitor->id,
        'Connection timeout',
        1
    );

    $job->handle();

    $monitor->refresh();

    // Transient count should be incremented
    expect($monitor->transient_failures_count)->toBe(6);
});
