<?php

use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\artisan;

beforeEach(function () {
    Queue::fake();
});

describe('BackfillDailyUptimesCommand', function () {
    it('dispatches jobs for missing dates', function () {
        $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
        
        // Create one record for 2 days ago
        $twoDaysAgo = now()->subDays(2)->toDateString();
        MonitorUptimeDaily::create([
            'monitor_id' => $monitor->id,
            'date' => $twoDaysAgo,
            'uptime_percentage' => 100,
        ]);

        // Run backfill for last 3 days
        // Days look back: yesterday (D1), 2 days ago (D2), 3 days ago (D3)
        // D2 already exists, so should dispatch jobs for D1 and D3
        artisan('uptime:backfill-dailies', ['--days' => 3])
            ->assertExitCode(0);

        $yesterday = now()->subDay()->toDateString();
        $threeDaysAgo = now()->subDays(3)->toDateString();

        Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor, $yesterday) {
            return $job->monitorId === $monitor->id && $job->date === $yesterday;
        });

        Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor, $threeDaysAgo) {
            return $job->monitorId === $monitor->id && $job->date === $threeDaysAgo;
        });

        Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 2);
    });

    it('dispatches jobs for all dates when force is used', function () {
        $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
        
        $yesterday = now()->subDay()->toDateString();
        MonitorUptimeDaily::create([
            'monitor_id' => $monitor->id,
            'date' => $yesterday,
            'uptime_percentage' => 100,
        ]);

        artisan('uptime:backfill-dailies', ['--days' => 1, '--force' => true])
            ->assertExitCode(0);

        Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 1);
    });

    it('filters by monitor id', function () {
        $monitor1 = Monitor::factory()->create(['uptime_check_enabled' => true]);
        $monitor2 = Monitor::factory()->create(['uptime_check_enabled' => true]);

        artisan('uptime:backfill-dailies', ['--days' => 1, '--monitor-id' => $monitor1->id])
            ->assertExitCode(0);

        Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor1) {
            return $job->monitorId === $monitor1->id;
        });

        Queue::assertNotPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor2) {
            return $job->monitorId === $monitor2->id;
        });
    });
});
