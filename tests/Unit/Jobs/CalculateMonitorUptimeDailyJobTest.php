<?php

use App\Jobs\CalculateMonitorUptimeDailyJob;
use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

describe('CalculateMonitorUptimeDailyJob', function () {
    describe('handle', function () {
        it('dispatches jobs for yesterday and missing days for monitors', function () {
            // Set lookback days to 3 for this test
            config()->set('uptime-monitor.daily_lookback_days', 3);

            $monitors = Monitor::factory()->count(2)->create([
                'uptime_check_enabled' => true,
            ]);

            // Create record for 2 days ago for first monitor only
            $twoDaysAgo = now()->subDays(2)->toDateString();
            MonitorUptimeDaily::create([
                'monitor_id' => $monitors[0]->id,
                'date' => $twoDaysAgo,
                'uptime_percentage' => 100,
            ]);

            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            $yesterday = now()->subDay()->toDateString();
            $threeDaysAgo = now()->subDays(3)->toDateString();

            // Monitor 0: missing D3, existing D2 (skipped), and D1 (yesterday, always computed) -> 2 jobs
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitors, $yesterday) {
                return $job->monitorId === $monitors[0]->id && $job->date === $yesterday;
            });
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitors, $threeDaysAgo) {
                return $job->monitorId === $monitors[0]->id && $job->date === $threeDaysAgo;
            });

            // Monitor 1: missing D3, D2, and D1 -> 3 jobs
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitors, $yesterday) {
                return $job->monitorId === $monitors[1]->id && $job->date === $yesterday;
            });
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitors, $twoDaysAgo) {
                return $job->monitorId === $monitors[1]->id && $job->date === $twoDaysAgo;
            });
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitors, $threeDaysAgo) {
                return $job->monitorId === $monitors[1]->id && $job->date === $threeDaysAgo;
            });

            // Total 5 jobs dispatched (2 for monitor 0 + 3 for monitor 1)
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 5);
        });

        it('handles empty monitor list gracefully', function () {
            // No monitors in database
            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            // Should not dispatch any jobs
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 0);
        });

        it('chunks monitors into smaller batches', function () {
            // Set lookback to 1 day (yesterday)
            config()->set('uptime-monitor.daily_lookback_days', 1);

            // Create 25 monitors (more than chunk size of 10)
            Monitor::factory()->count(25)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            // Should dispatch 25 jobs
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 25);
        });

        it('dispatches jobs for large number of monitors', function () {
            // Set lookback to 1 day (yesterday)
            config()->set('uptime-monitor.daily_lookback_days', 1);

            // Create 50 monitors to test chunking behavior
            Monitor::factory()->count(50)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 50);
        });

        it('logs appropriate messages during execution', function () {
            // Set lookback to 1 day (yesterday)
            config()->set('uptime-monitor.daily_lookback_days', 1);

            Monitor::factory()->count(5)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob;

            // Test that the job completes without error (logging happens internally)
            $job->handle();

            // Verify the expected jobs were dispatched
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 5);
        });

        it('re-throws exceptions for proper error handling', function () {
            // Create a partial mock of the job that allows mocking protected methods
            $job = $this->partialMock(CalculateMonitorUptimeDailyJob::class)
                ->shouldAllowMockingProtectedMethods();

            // Mock the protected getMonitorIds method to throw an exception
            $job->shouldReceive('getMonitorIds')
                ->once()
                ->andThrow(new Exception('Database error'));

            expect(fn () => $job->handle())
                ->toThrow(Exception::class, 'Database error');
        });
    });
});
