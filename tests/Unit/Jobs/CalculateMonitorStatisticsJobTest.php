<?php

use App\Jobs\CalculateMonitorStatisticsJob;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use Illuminate\Support\Facades\Queue;

describe('CalculateMonitorStatisticsJob', function () {
    describe('handle', function () {
        it('processes all public monitors sequentially', function () {
            Queue::fake();

            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => 'up',
                'created_at' => now(),
            ]);

            MonitorUptimeDaily::updateOrInsert(
                ['monitor_id' => $monitor->id, 'date' => now()->toDateString()],
                ['uptime_percentage' => 100, 'total_checks' => 1, 'failed_checks' => 0]
            );

            $job = new CalculateMonitorStatisticsJob;
            $job->handle();

            $this->assertDatabaseHas('monitor_statistics', ['monitor_id' => $monitor->id]);

            Queue::assertNothingPushed();
        });

        it('calculates statistics for a single monitor', function () {
            $monitor1 = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);
            $monitor2 = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $monitor1->id,
                'uptime_status' => 'up',
                'created_at' => now(),
            ]);

            MonitorUptimeDaily::updateOrInsert(
                ['monitor_id' => $monitor1->id, 'date' => now()->toDateString()],
                ['uptime_percentage' => 100, 'total_checks' => 1, 'failed_checks' => 0]
            );

            $job = new CalculateMonitorStatisticsJob($monitor1->id);
            $job->handle();

            $this->assertDatabaseHas('monitor_statistics', ['monitor_id' => $monitor1->id]);
            $this->assertDatabaseMissing('monitor_statistics', ['monitor_id' => $monitor2->id]);
        });

        it('only processes public monitors', function () {
            $monitor = Monitor::factory()->create([
                'is_public' => false,
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorStatisticsJob;
            $job->handle();

            $this->assertDatabaseMissing('monitor_statistics', ['monitor_id' => $monitor->id]);
        });
    });
});
