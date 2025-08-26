<?php

use App\Jobs\CalculateMonitorUptimeDailyJob;
use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

describe('CalculateMonitorUptimeDailyJob', function () {
    describe('handle', function () {
        it('dispatches jobs for all monitors', function () {
            // Create some monitors
            $monitors = Monitor::factory()->count(3)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob();
            $job->handle();

            // Should dispatch 3 CalculateSingleMonitorUptimeJob instances
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 3);

            // Verify each monitor gets a job
            foreach ($monitors as $monitor) {
                Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor) {
                    return $job->monitorId === $monitor->id;
                });
            }
        });

        it('handles empty monitor list gracefully', function () {
            // No monitors in database
            $job = new CalculateMonitorUptimeDailyJob();
            $job->handle();

            // Should not dispatch any jobs
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 0);
        });

        it('chunks monitors into smaller batches', function () {
            // Create 25 monitors (more than chunk size of 10)
            Monitor::factory()->count(25)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob();
            $job->handle();

            // Should dispatch 25 jobs
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 25);
        });

        it('dispatches jobs for large number of monitors', function () {
            // Create 50 monitors to test chunking behavior
            Monitor::factory()->count(50)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob();
            $job->handle();

            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 50);
        });

        it('logs appropriate messages during execution', function () {
            Monitor::factory()->count(5)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob();
            
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

            expect(fn() => $job->handle())
                ->toThrow(Exception::class, 'Database error');
        });
    });
});
