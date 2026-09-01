<?php

use App\Jobs\CalculateMonitorBatchUptimeJob;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;

describe('CalculateMonitorBatchUptimeJob', function () {
    it('calculates and upserts daily uptime for multiple monitors in a batch', function () {
        $date = now()->subDay()->toDateString();

        $monitor1 = Monitor::factory()->create();
        $monitor2 = Monitor::factory()->create();

        // Monitor 1: 3 checks (2 up, 1 down) = 66.67%
        MonitorHistory::create([
            'monitor_id' => $monitor1->id,
            'uptime_status' => 'up',
            'response_time' => 120,
            'status_code' => 200,
            'created_at' => now()->subDay()->setHour(10),
            'checked_at' => now()->subDay()->setHour(10),
        ]);
        MonitorHistory::create([
            'monitor_id' => $monitor1->id,
            'uptime_status' => 'up',
            'response_time' => 150,
            'status_code' => 200,
            'created_at' => now()->subDay()->setHour(11),
            'checked_at' => now()->subDay()->setHour(11),
        ]);
        MonitorHistory::create([
            'monitor_id' => $monitor1->id,
            'uptime_status' => 'down',
            'response_time' => 0,
            'status_code' => 500,
            'created_at' => now()->subDay()->setHour(12),
            'checked_at' => now()->subDay()->setHour(12),
        ]);

        // Monitor 2: 2 checks (2 up) = 100%
        MonitorHistory::create([
            'monitor_id' => $monitor2->id,
            'uptime_status' => 'up',
            'response_time' => 80,
            'status_code' => 200,
            'created_at' => now()->subDay()->setHour(10),
            'checked_at' => now()->subDay()->setHour(10),
        ]);
        MonitorHistory::create([
            'monitor_id' => $monitor2->id,
            'uptime_status' => 'up',
            'response_time' => 90,
            'status_code' => 200,
            'created_at' => now()->subDay()->setHour(11),
            'checked_at' => now()->subDay()->setHour(11),
        ]);

        $job = new CalculateMonitorBatchUptimeJob([$monitor1->id, $monitor2->id], $date);
        app()->call([$job, 'handle']);

        $daily1 = MonitorUptimeDaily::where('monitor_id', $monitor1->id)->where('date', $date)->first();
        $daily2 = MonitorUptimeDaily::where('monitor_id', $monitor2->id)->where('date', $date)->first();

        expect($daily1)->not->toBeNull();
        expect((float) $daily1->uptime_percentage)->toBe(66.67);
        expect((int) $daily1->total_checks)->toBe(3);
        expect((int) $daily1->failed_checks)->toBe(1);

        expect($daily2)->not->toBeNull();
        expect((float) $daily2->uptime_percentage)->toBe(100.0);
        expect((int) $daily2->total_checks)->toBe(2);
        expect((int) $daily2->failed_checks)->toBe(0);
    });

    it('handles batch with monitors having zero checks mixed with monitors having checks', function () {
        $date = now()->subDay()->toDateString();

        $monitorWithChecks = Monitor::factory()->create();
        $monitorWithoutChecks = Monitor::factory()->create();

        MonitorHistory::create([
            'monitor_id' => $monitorWithChecks->id,
            'uptime_status' => 'up',
            'response_time' => 100,
            'status_code' => 200,
            'created_at' => now()->subDay()->setHour(10),
            'checked_at' => now()->subDay()->setHour(10),
        ]);

        $job = new CalculateMonitorBatchUptimeJob([$monitorWithChecks->id, $monitorWithoutChecks->id], $date);
        app()->call([$job, 'handle']);

        $daily1 = MonitorUptimeDaily::where('monitor_id', $monitorWithChecks->id)->where('date', $date)->first();
        $daily2 = MonitorUptimeDaily::where('monitor_id', $monitorWithoutChecks->id)->where('date', $date)->first();

        expect($daily1)->not->toBeNull();
        expect((float) $daily1->avg_response_time)->toBe(100.0);

        expect($daily2)->not->toBeNull();
        expect((int) $daily2->total_checks)->toBe(0);
        expect($daily2->avg_response_time)->toBeNull();
    });

    it('handles empty monitor ids gracefully', function () {
        $job = new CalculateMonitorBatchUptimeJob([], now()->toDateString());
        app()->call([$job, 'handle']);

        expect(true)->toBeTrue();
    });
});
