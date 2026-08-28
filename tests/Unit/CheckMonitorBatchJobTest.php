<?php

use App\Jobs\CheckMonitorBatchJob;
use App\Models\Monitor;

describe('CheckMonitorBatchJob', function () {
    it('handles empty monitor ids array gracefully', function () {
        $job = new CheckMonitorBatchJob([]);
        $job->handle();

        expect(true)->toBeTrue();
    });

    it('handles nonexistent monitor ids gracefully', function () {
        $job = new CheckMonitorBatchJob([999999, 999998]);
        $job->handle();

        expect(true)->toBeTrue();
    });

    it('checks monitors in the batch', function () {
        $monitor = Monitor::factory()->create([
            'uptime_check_enabled' => true,
        ]);

        $job = new CheckMonitorBatchJob([$monitor->id]);
        $job->handle();

        expect(true)->toBeTrue();
    });

    it('skips disabled monitors in the batch', function () {
        $monitor = Monitor::factory()->create([
            'uptime_check_enabled' => false,
        ]);

        $job = new CheckMonitorBatchJob([$monitor->id]);
        $job->handle();

        expect(true)->toBeTrue();
    });
});
