<?php

use App\Jobs\SendWeeklyMonitorReportJob;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('displays warning when no eligible users exist', function () {
    $this->artisan('reports:send-weekly')
        ->expectsOutputToContain('No eligible users found for weekly reports.')
        ->assertSuccessful();
});

it('dispatches weekly reports to the default queue for eligible users', function () {
    Queue::fake();

    $user = User::factory()->create(['weekly_report_enabled' => true]);
    $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $user->monitors()->attach($monitor);

    // User without active monitor shouldn't be included
    User::factory()->create(['weekly_report_enabled' => true]);

    $this->artisan('reports:send-weekly')
        ->expectsOutputToContain('Dispatching weekly reports for 1 user(s)...')
        ->expectsOutputToContain('Successfully dispatched 1 weekly report(s) (to default queue).')
        ->assertSuccessful();

    Queue::assertPushedOn('default', SendWeeklyMonitorReportJob::class, function ($job) use ($user) {
        return $job->user->id === $user->id;
    });
});

it('supports --dry-run option without dispatching', function () {
    Queue::fake();

    $user = User::factory()->create(['weekly_report_enabled' => true]);
    $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $user->monitors()->attach($monitor);

    $this->artisan('reports:send-weekly --dry-run')
        ->expectsOutputToContain('Dry run: Found 1 eligible user(s).')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('can target a specific user with --user and --sync', function () {
    $user = User::factory()->create(['weekly_report_enabled' => true]);
    $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $user->monitors()->attach($monitor);

    $otherUser = User::factory()->create(['weekly_report_enabled' => true]);
    $otherMonitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $otherUser->monitors()->attach($otherMonitor);

    $this->artisan("reports:send-weekly --user={$user->email} --sync")
        ->expectsOutputToContain('Dispatching weekly reports for 1 user(s)...')
        ->expectsOutputToContain('Successfully dispatched 1 weekly report(s) (synchronously).')
        ->assertSuccessful();
});
