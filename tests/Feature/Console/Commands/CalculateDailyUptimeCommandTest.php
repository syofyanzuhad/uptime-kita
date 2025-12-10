<?php

use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Carbon::setTestNow(now());
    Queue::fake();
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('CalculateDailyUptimeCommand', function () {
    describe('handle', function () {
        it('uses today as default date when no date provided', function () {
            $monitor = Monitor::factory()->create();

            $this->artisan('uptime:calculate-daily')
                ->expectsOutput('Starting daily uptime calculation for date: '.now()->toDateString())
                ->assertSuccessful();
        });

        it('accepts custom date argument', function () {
            $monitor = Monitor::factory()->create();
            $date = '2024-01-15';

            $this->artisan('uptime:calculate-daily', ['date' => $date])
                ->expectsOutput("Starting daily uptime calculation for date: {$date}")
                ->assertSuccessful();
        });

        it('validates date format', function () {
            $this->artisan('uptime:calculate-daily', ['date' => 'invalid-date'])
                ->expectsOutput('Invalid date format: invalid-date. Please use Y-m-d format (e.g., 2024-01-15)')
                ->assertFailed();
        });

        it('dispatches jobs for all monitors when no specific monitor ID provided', function () {
            // Create multiple monitors
            Monitor::factory()->count(3)->create();

            $this->artisan('uptime:calculate-daily')
                ->assertSuccessful();

            // Should dispatch one job per monitor
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 3);
        });

        it('dispatches job for specific monitor when monitor ID provided', function () {
            $monitor = Monitor::factory()->create();
            $otherMonitor = Monitor::factory()->create();

            $this->artisan('uptime:calculate-daily', ['--monitor-id' => $monitor->id])
                ->assertSuccessful();

            // Should dispatch only one job for the specific monitor
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 1);
            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor) {
                return $job->monitorId === $monitor->id;
            });
        });

        it('shows error for non-existent monitor ID', function () {
            $nonExistentId = 99999;

            $this->artisan('uptime:calculate-daily', ['--monitor-id' => $nonExistentId])
                ->expectsOutput("Monitor with ID {$nonExistentId} not found")
                ->assertFailed();
        });

        it('handles force option correctly', function () {
            $monitor = Monitor::factory()->create();

            $this->artisan('uptime:calculate-daily', [
                '--monitor-id' => $monitor->id,
                '--force' => true,
            ])
                ->assertSuccessful();

            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, 1);
        });

        it('shows completion message with statistics', function () {
            Monitor::factory()->count(5)->create();

            $this->artisan('uptime:calculate-daily')
                ->expectsOutputToContain('Daily uptime calculation completed')
                ->expectsOutputToContain('Total monitors processed: 5')
                ->assertSuccessful();
        });

        it('handles empty monitor list gracefully', function () {
            $this->artisan('uptime:calculate-daily')
                ->expectsOutput('No monitors found to calculate uptime for')
                ->assertSuccessful();
        });

        it('passes correct date to job', function () {
            $monitor = Monitor::factory()->create();
            $customDate = '2024-06-15';

            $this->artisan('uptime:calculate-daily', ['date' => $customDate])
                ->assertSuccessful();

            Queue::assertPushed(CalculateSingleMonitorUptimeJob::class, function ($job) use ($monitor, $customDate) {
                return $job->monitorId === $monitor->id && $job->date === $customDate;
            });
        });
    });

    describe('date validation', function () {
        it('accepts valid date formats', function () {
            $validDates = ['2024-01-01', '2024-12-31', '2023-06-15'];

            foreach ($validDates as $date) {
                Monitor::factory()->create();

                $this->artisan('uptime:calculate-daily', ['date' => $date])
                    ->assertSuccessful();
            }
        });

        it('rejects invalid date formats', function () {
            $invalidDates = ['2024/01/01', '01-01-2024', '2024-13-01', '2024-01-32', 'today', ''];

            foreach ($invalidDates as $date) {
                $this->artisan('uptime:calculate-daily', ['date' => $date])
                    ->assertFailed();
            }
        });
    });

    describe('monitor ID validation', function () {
        it('accepts valid monitor IDs', function () {
            $monitor = Monitor::factory()->create();

            $this->artisan('uptime:calculate-daily', ['--monitor-id' => $monitor->id])
                ->assertSuccessful();
        });

        it('rejects non-numeric monitor IDs', function () {
            $this->artisan('uptime:calculate-daily', ['--monitor-id' => 'abc'])
                ->expectsOutput('Invalid monitor ID: abc. Monitor ID must be a number.')
                ->assertFailed();
        });

        it('rejects negative monitor IDs', function () {
            $this->artisan('uptime:calculate-daily', ['--monitor-id' => '-1'])
                ->expectsOutput('Invalid monitor ID: -1. Monitor ID must be a number.')
                ->assertFailed();
        });
    });
});
