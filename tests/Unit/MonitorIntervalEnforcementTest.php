<?php

use App\Models\Monitor;
use Illuminate\Support\Facades\Config;

it('enforces minimum uptime check interval from config', function () {
    // Set minimum interval to 5
    Config::set('uptime-monitor.uptime_check.minimum_run_interval_in_minutes', 5);

    $monitor = new Monitor();
    $monitor->uptime_check_interval_in_minutes = 1;

    // Even though we set it to 1, it should return 5 because of the accessor
    expect($monitor->uptime_check_interval_in_minutes)->toBe(5);

    $monitor->uptime_check_interval_in_minutes = 10;
    // Should still return 10 since it is above the minimum
    expect($monitor->uptime_check_interval_in_minutes)->toBe(10);
});

it('does not enforce minimum if config is 0', function () {
    Config::set('uptime-monitor.uptime_check.minimum_run_interval_in_minutes', 0);

    $monitor = new Monitor();
    $monitor->uptime_check_interval_in_minutes = 1;

    expect($monitor->uptime_check_interval_in_minutes)->toBe(1);
});
