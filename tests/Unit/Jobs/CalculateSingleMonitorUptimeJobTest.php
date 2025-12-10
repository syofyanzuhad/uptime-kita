<?php

use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Services\MonitorPerformanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(now());
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);
    $this->date = '2024-01-01';
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('CalculateSingleMonitorUptimeJob', function () {
    describe('uniqueId', function () {
        it('generates unique ID correctly', function () {
            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);

            $uniqueId = $job->uniqueId();

            expect($uniqueId)->toBe("uptime_calc_{$this->monitor->id}_{$this->date}");
        });

        it('generates different IDs for different monitors', function () {
            $monitor2 = Monitor::factory()->create();

            $job1 = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job2 = new CalculateSingleMonitorUptimeJob($monitor2->id, $this->date);

            expect($job1->uniqueId())->not->toBe($job2->uniqueId());
        });

        it('generates different IDs for different dates', function () {
            $job1 = new CalculateSingleMonitorUptimeJob($this->monitor->id, '2024-01-01');
            $job2 = new CalculateSingleMonitorUptimeJob($this->monitor->id, '2024-01-02');

            expect($job1->uniqueId())->not->toBe($job2->uniqueId());
        });
    });

    describe('constructor', function () {
        it('sets default date to today when not provided', function () {
            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id);

            expect($job->date)->toBe(Carbon::today()->toDateString());
        });

        it('uses provided date when given', function () {
            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);

            expect($job->date)->toBe($this->date);
        });
    });

    describe('handle', function () {
        it('calculates uptime correctly with mixed status data', function () {
            $startDate = Carbon::parse($this->date)->startOfDay();

            // Create monitor histories: 8 up, 2 down = 80% uptime
            MonitorHistory::factory()->count(8)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $startDate->copy()->addHours(1),
            ]);

            MonitorHistory::factory()->count(2)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $startDate->copy()->addHours(2),
            ]);

            // Mock the performance service
            $performanceService = mock(MonitorPerformanceService::class);
            $performanceService->shouldReceive('aggregateDailyMetrics')
                ->with($this->monitor->id, $this->date)
                ->andReturn([
                    'avg_response_time' => 250,
                    'min_response_time' => 100,
                    'max_response_time' => 400,
                    'total_checks' => 10,
                    'failed_checks' => 2,
                ]);

            $this->app->instance(MonitorPerformanceService::class, $performanceService);

            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job->handle();

            // Check that uptime record was created
            $this->assertDatabaseHas('monitor_uptime_dailies', [
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 80.0,
                'avg_response_time' => 250,
                'total_checks' => 10,
                'failed_checks' => 2,
            ]);
        });

        it('handles 100% uptime correctly', function () {
            $startDate = Carbon::parse($this->date)->startOfDay();

            // Create only successful checks
            MonitorHistory::factory()->count(10)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $startDate->copy()->addHours(1),
            ]);

            $performanceService = mock(MonitorPerformanceService::class);
            $performanceService->shouldReceive('aggregateDailyMetrics')
                ->andReturn(['avg_response_time' => 200, 'min_response_time' => 150, 'max_response_time' => 300, 'total_checks' => 10, 'failed_checks' => 0]);

            $this->app->instance(MonitorPerformanceService::class, $performanceService);

            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job->handle();

            $this->assertDatabaseHas('monitor_uptime_dailies', [
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 100.0,
            ]);
        });

        it('handles 0% uptime correctly', function () {
            $startDate = Carbon::parse($this->date)->startOfDay();

            // Create only failed checks
            MonitorHistory::factory()->count(5)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $startDate->copy()->addHours(1),
            ]);

            $performanceService = mock(MonitorPerformanceService::class);
            $performanceService->shouldReceive('aggregateDailyMetrics')
                ->andReturn(['avg_response_time' => null, 'min_response_time' => null, 'max_response_time' => null, 'total_checks' => 5, 'failed_checks' => 5]);

            $this->app->instance(MonitorPerformanceService::class, $performanceService);

            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job->handle();

            $this->assertDatabaseHas('monitor_uptime_dailies', [
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 0.0,
            ]);
        });

        it('handles no data gracefully', function () {
            // No monitor histories for the date
            $performanceService = mock(MonitorPerformanceService::class);
            $performanceService->shouldReceive('aggregateDailyMetrics')
                ->andReturn([]);

            $this->app->instance(MonitorPerformanceService::class, $performanceService);

            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job->handle();

            $this->assertDatabaseHas('monitor_uptime_dailies', [
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 0.0,
            ]);
        });

        it('skips calculation for non-existent monitor', function () {
            $nonExistentId = 99999;

            $job = new CalculateSingleMonitorUptimeJob($nonExistentId, $this->date);
            $job->handle();

            // Should not create any records
            $this->assertDatabaseMissing('monitor_uptime_dailies', [
                'monitor_id' => $nonExistentId,
                'date' => $this->date,
            ]);
        });

        it('throws exception for invalid date format', function () {
            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, 'invalid-date');

            expect(fn () => $job->handle())
                ->toThrow(Exception::class, 'Invalid date format: invalid-date');
        });

        it('updates existing uptime record', function () {
            // Create existing record
            DB::table('monitor_uptime_dailies')->insert([
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 50.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $startDate = Carbon::parse($this->date)->startOfDay();

            // Create new data that should result in 80% uptime
            MonitorHistory::factory()->count(8)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $startDate->copy()->addHours(1),
            ]);

            MonitorHistory::factory()->count(2)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $startDate->copy()->addHours(2),
            ]);

            $performanceService = mock(MonitorPerformanceService::class);
            $performanceService->shouldReceive('aggregateDailyMetrics')
                ->andReturn(['avg_response_time' => 200, 'min_response_time' => 150, 'max_response_time' => 300, 'total_checks' => 10, 'failed_checks' => 2]);

            $this->app->instance(MonitorPerformanceService::class, $performanceService);

            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job->handle();

            // Should update the existing record to 80%
            $this->assertDatabaseHas('monitor_uptime_dailies', [
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 80.0,
            ]);

            // Should only have one record
            $count = DB::table('monitor_uptime_dailies')
                ->where('monitor_id', $this->monitor->id)
                ->where('date', $this->date)
                ->count();

            expect($count)->toBe(1);
        });

        it('rounds uptime percentage correctly', function () {
            $startDate = Carbon::parse($this->date)->startOfDay();

            // Create 3 up, 1 down = 75% uptime (no rounding needed)
            MonitorHistory::factory()->count(3)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $startDate->copy()->addHours(1),
            ]);

            MonitorHistory::factory()->count(1)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $startDate->copy()->addHours(2),
            ]);

            $performanceService = mock(MonitorPerformanceService::class);
            $performanceService->shouldReceive('aggregateDailyMetrics')
                ->andReturn([]);

            $this->app->instance(MonitorPerformanceService::class, $performanceService);

            $job = new CalculateSingleMonitorUptimeJob($this->monitor->id, $this->date);
            $job->handle();

            $this->assertDatabaseHas('monitor_uptime_dailies', [
                'monitor_id' => $this->monitor->id,
                'date' => $this->date,
                'uptime_percentage' => 75.0,
            ]);
        });
    });
});
