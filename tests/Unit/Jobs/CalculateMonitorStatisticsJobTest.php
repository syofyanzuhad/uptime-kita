<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateMonitorStatisticsJob;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CalculateMonitorStatisticsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_individual_jobs_for_all_public_monitors()
    {
        Queue::fake();

        // Create a public monitor
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // Run the job
        $job = new CalculateMonitorStatisticsJob;
        $job->handle();

        // Check if individual job was dispatched
        Queue::assertPushed(CalculateMonitorStatisticsJob::class, function ($job) use ($monitor) {
            return $job->uniqueId() === 'monitor-'.$monitor->id;
        });
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

        // Create history for monitor1
        MonitorHistory::factory()->create([
            'monitor_id' => $monitor1->id,
            'uptime_status' => 'up',
            'created_at' => now(),
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
