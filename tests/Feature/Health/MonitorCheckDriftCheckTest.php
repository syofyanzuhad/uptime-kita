<?php

use App\Health\Checks\MonitorCheckDriftCheck;
use App\Models\Monitor;
use Spatie\Health\Enums\Status;

it('returns ok when there are no active monitors', function () {
    $result = MonitorCheckDriftCheck::new()->run();

    expect($result->status)->toBe(Status::ok());
});

it('returns ok when all monitors are checked recently on schedule', function () {
    Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'uptime_check_interval_in_minutes' => 5,
        'uptime_last_check_date' => now()->subMinutes(2),
    ]);

    $result = MonitorCheckDriftCheck::new()->run();

    expect($result->status)->toBe(Status::ok());
});

it('returns warning when a monitor is delayed past warning drift threshold', function () {
    Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'uptime_check_interval_in_minutes' => 5,
        'uptime_last_check_date' => now()->subMinutes(8), // 3 min drift (>= 2 min warning)
    ]);

    $result = MonitorCheckDriftCheck::new()
        ->warnWhenDriftExceedsMinutes(2)
        ->failWhenDriftExceedsMinutes(5)
        ->run();

    expect($result->status)->toBe(Status::warning());
    expect($result->getNotificationMessage())->toContain('delayed by up to 3 min');
});

it('returns failed when a monitor is delayed past failure drift threshold', function () {
    Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'uptime_check_interval_in_minutes' => 5,
        'uptime_last_check_date' => now()->subMinutes(12), // 7 min drift (>= 5 min failure)
    ]);

    $result = MonitorCheckDriftCheck::new()
        ->warnWhenDriftExceedsMinutes(2)
        ->failWhenDriftExceedsMinutes(5)
        ->run();

    expect($result->status)->toBe(Status::failed());
    expect($result->getNotificationMessage())->toContain('delayed by up to 7 min');
});

it('ignores disabled monitors when calculating drift', function () {
    Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'uptime_check_interval_in_minutes' => 5,
        'uptime_last_check_date' => now()->subHours(2),
    ]);

    $result = MonitorCheckDriftCheck::new()->run();

    expect($result->status)->toBe(Status::ok());
});
