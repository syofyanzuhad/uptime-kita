use App\Jobs\CalculateMonitorBatchUptimeJob;
use App\Jobs\CalculateMonitorUptimeDailyJob;
use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

describe('CalculateMonitorUptimeDailyJob', function () {
    describe('handle', function () {
        it('dispatches batch jobs for yesterday and missing days for monitors', function () {
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

            // Yesterday batch includes both monitors
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, function ($job) use ($monitors, $yesterday) {
                return $job->date === $yesterday && in_array($monitors[0]->id, $job->monitorIds) && in_array($monitors[1]->id, $job->monitorIds);
            });

            // 3 days ago batch includes both monitors
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, function ($job) use ($monitors, $threeDaysAgo) {
                return $job->date === $threeDaysAgo && in_array($monitors[0]->id, $job->monitorIds) && in_array($monitors[1]->id, $job->monitorIds);
            });

            // 2 days ago batch includes only monitor 1 (since monitor 0 already exists)
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, function ($job) use ($monitors, $twoDaysAgo) {
                return $job->date === $twoDaysAgo && ! in_array($monitors[0]->id, $job->monitorIds) && in_array($monitors[1]->id, $job->monitorIds);
            });

            // Total 3 batch jobs dispatched (1 per date)
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, 3);
        });

        it('handles empty monitor list gracefully', function () {
            // No monitors in database
            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            // Should not dispatch any jobs
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, 0);
        });

        it('chunks monitors into smaller batches based on config', function () {
            // Set lookback to 1 day (yesterday)
            config()->set('uptime-monitor.daily_lookback_days', 1);
            config()->set('uptime-monitor.daily_uptime_chunk_size', 10);

            // Create 25 monitors (chunk size 10 -> ceil(25/10) = 3 jobs)
            Monitor::factory()->count(25)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            // Should dispatch 3 chunk jobs
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, 3);
        });

        it('dispatches jobs for large number of monitors in chunks', function () {
            // Set lookback to 1 day (yesterday)
            config()->set('uptime-monitor.daily_lookback_days', 1);
            config()->set('uptime-monitor.daily_uptime_chunk_size', 20);

            // Create 50 monitors (chunk size 20 -> ceil(50/20) = 3 jobs)
            Monitor::factory()->count(50)->create([
                'uptime_check_enabled' => true,
            ]);

            $job = new CalculateMonitorUptimeDailyJob;
            $job->handle();

            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, 3);
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

            // Verify the expected batch job was dispatched
            Queue::assertPushed(CalculateMonitorBatchUptimeJob::class, 1);
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

        it('returns unique id for queue locking', function () {
            $job = new CalculateMonitorUptimeDailyJob;
            expect($job->uniqueId())->toBe('uptime-daily-dispatcher');
        });
    });
});
