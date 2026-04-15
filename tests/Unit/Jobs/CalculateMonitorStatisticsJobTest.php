<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateMonitorStatisticsJob;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateMonitorStatisticsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_statistics_for_all_public_monitors()
    {
        // Create a public monitor
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // Create some history
        MonitorHistory::factory()->count(10)->create([
            'monitor_id' => $monitor->id,
            'uptime_status' => 'up',
            'response_time' => 100,
        ]);

        // Run the job
        $job = new CalculateMonitorStatisticsJob;
        $job->handle();

        // Check if statistics were created
        $this->assertDatabaseHas('monitor_statistics', [
            'monitor_id' => $monitor->id,
            'uptime_24h' => 100.0,
            'avg_response_time_24h' => 100,
        ]);
    }

    public function test_it_calculates_statistics_for_a_single_monitor()
    {
        // Create two public monitors
        $monitor1 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor2 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // Create history for both
        MonitorHistory::factory()->create([
            'monitor_id' => $monitor1->id,
            'uptime_status' => 'up',
        ]);
        MonitorHistory::factory()->create([
            'monitor_id' => $monitor2->id,
            'uptime_status' => 'up',
        ]);

        // Run the job for only monitor1
        $job = new CalculateMonitorStatisticsJob($monitor1->id);
        $job->handle();

        // Check if statistics were created only for monitor1
        $this->assertDatabaseHas('monitor_statistics', ['monitor_id' => $monitor1->id]);
        $this->assertDatabaseMissing('monitor_statistics', ['monitor_id' => $monitor2->id]);
    }

    public function test_it_only_processes_public_monitors()
    {
        // Create a private monitor
        $monitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        // Run the job
        $job = new CalculateMonitorStatisticsJob;
        $job->handle();

        // Check that no statistics were created
        $this->assertDatabaseMissing('monitor_statistics', ['monitor_id' => $monitor->id]);
    }
}
