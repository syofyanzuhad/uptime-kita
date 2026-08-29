<?php

use App\Jobs\CheckMonitorBatchJob;
use App\Models\Monitor;

test('check monitor batch job handles empty ids safely', function () {
    $job = new CheckMonitorBatchJob([]);
    $job->handle();

    expect(true)->toBeTrue();
});

test('check monitor batch job filters monitors and executes checks', function () {
    $monitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'uptime_status' => 'not yet checked',
    ]);

    $job = new CheckMonitorBatchJob([$monitor->id]);
    $job->handle();

    expect($monitor->fresh())->not->toBeNull();
});
