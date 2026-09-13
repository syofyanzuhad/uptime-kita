<?php

use App\Jobs\SendWeeklyMonitorReportJob;
use App\Models\Monitor;
use App\Models\MonitorStatistic;
use App\Models\User;
use App\Notifications\WeeklyMonitorReport;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('users with no active monitors do not receive the weekly report', function () {
    $user = User::factory()->create([
        'weekly_report_enabled' => true,
    ]);

    // Attach a disabled monitor
    $monitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
    ]);
    $user->monitors()->attach($monitor->id);

    $job = new SendWeeklyMonitorReportJob($user);
    $job->handle();

    Notification::assertNothingSent();
});

test('users with weekly_report_enabled set to false are skipped', function () {
    $user = User::factory()->create([
        'weekly_report_enabled' => false,
    ]);

    $monitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
    ]);
    $user->monitors()->attach($monitor->id);

    $job = new SendWeeklyMonitorReportJob($user);
    $job->handle();

    Notification::assertNothingSent();
});

test('eligible users receive weekly report with monitor summaries', function () {
    $user = User::factory()->create([
        'name' => 'Report User',
        'email' => 'report@example.com',
        'weekly_report_enabled' => true,
        'weekly_report_timezone' => 'Asia/Jakarta',
    ]);

    $monitor = Monitor::factory()->create([
        'display_name' => 'Production Server',
        'uptime_status' => 'up',
        'uptime_check_enabled' => true,
    ]);
    $user->monitors()->attach($monitor->id);

    MonitorStatistic::create([
        'monitor_id' => $monitor->id,
        'uptime_7d' => 99.95,
        'avg_response_time_24h' => 110,
        'incidents_7d' => 0,
        'calculated_at' => now(),
    ]);

    $job = new SendWeeklyMonitorReportJob($user);
    $job->handle();

    Notification::assertSentTo($user, WeeklyMonitorReport::class, function ($notification) use ($monitor) {
        $summaries = $notification->monitorSummaries;

        expect($summaries)->toHaveCount(1);
        expect($summaries->first()['monitor_id'])->toBe($monitor->id);
        expect($summaries->first()['display_name'])->toBe('Production Server');
        expect($summaries->first()['uptime_7d'])->toBe(99.95);
        expect($summaries->first()['is_up_now'])->toBeTrue();

        expect($notification->fleetTotals['total_monitors'])->toBe(1);
        expect($notification->fleetTotals['up_monitors'])->toBe(1);

        return true;
    });
});
