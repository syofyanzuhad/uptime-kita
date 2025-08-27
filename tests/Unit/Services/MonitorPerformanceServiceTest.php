<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorPerformanceHourly;
use App\Services\MonitorPerformanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(now());
    $this->service = app(MonitorPerformanceService::class);
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('MonitorPerformanceService', function () {
    describe('updateHourlyMetrics', function () {
        it('creates new hourly performance record when none exists', function () {
            $responseTime = 250;

            $this->service->updateHourlyMetrics($this->monitor->id, $responseTime, true);

            $performance = MonitorPerformanceHourly::where('monitor_id', $this->monitor->id)->first();
            expect($performance)->not->toBeNull();
            expect($performance->success_count)->toBe(1);
            expect($performance->failure_count)->toBe(0);
        });

        it('updates existing hourly performance record', function () {
            $hour = Carbon::now()->startOfHour();
            $existing = MonitorPerformanceHourly::create([
                'monitor_id' => $this->monitor->id,
                'hour' => $hour,
                'success_count' => 5,
                'failure_count' => 2,
            ]);

            $this->service->updateHourlyMetrics($this->monitor->id, 300, true);

            $existing->refresh();
            expect($existing->success_count)->toBe(6);
            expect($existing->failure_count)->toBe(2);
        });

        it('increments failure count for failed checks', function () {
            $this->service->updateHourlyMetrics($this->monitor->id, null, false);

            $performance = MonitorPerformanceHourly::where('monitor_id', $this->monitor->id)->first();
            expect($performance->success_count)->toBe(0);
            expect($performance->failure_count)->toBe(1);
        });

        it('calculates response time metrics for successful checks', function () {
            // Create some historical data
            $hour = Carbon::now()->startOfHour();
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 100,
                'created_at' => $hour->copy()->addMinutes(10),
                'checked_at' => $hour->copy()->addMinutes(10),
            ]);
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 200,
                'created_at' => $hour->copy()->addMinutes(20),
                'checked_at' => $hour->copy()->addMinutes(20),
            ]);

            $this->service->updateHourlyMetrics($this->monitor->id, 300, true);

            $performance = MonitorPerformanceHourly::where('monitor_id', $this->monitor->id)->first();
            expect($performance->avg_response_time)->toBeGreaterThan(0);
            expect($performance->p95_response_time)->toBeGreaterThan(0);
            expect($performance->p99_response_time)->toBeGreaterThan(0);
        });
    });

    describe('aggregateDailyMetrics', function () {
        it('returns correct metrics for a day with data', function () {
            $date = '2024-01-01';
            $startDate = Carbon::parse($date)->startOfDay();

            // Create test data
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 150,
                'created_at' => $startDate->copy()->addHours(2),
                'checked_at' => $startDate->copy()->addHours(2),
            ]);
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'response_time' => null,
                'created_at' => $startDate->copy()->addHours(4),
                'checked_at' => $startDate->copy()->addHours(4),
            ]);
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 250,
                'created_at' => $startDate->copy()->addHours(6),
                'checked_at' => $startDate->copy()->addHours(6),
            ]);

            $metrics = $this->service->aggregateDailyMetrics($this->monitor->id, $date);

            expect($metrics['total_checks'])->toBe(3);
            expect($metrics['failed_checks'])->toBe(1);
            expect($metrics['avg_response_time'])->toBe(200.0); // (150 + 250) / 2
            expect($metrics['min_response_time'])->toBe(150);
            expect($metrics['max_response_time'])->toBe(250);
        });

        it('returns zeros for day with no data', function () {
            $date = '2024-01-01';

            $metrics = $this->service->aggregateDailyMetrics($this->monitor->id, $date);

            expect($metrics['total_checks'])->toBe(0);
            expect($metrics['failed_checks'])->toBe(0);
            expect($metrics['avg_response_time'])->toBeNull();
            expect($metrics['min_response_time'])->toBeNull();
            expect($metrics['max_response_time'])->toBeNull();
        });
    });

    describe('getResponseTimeStats', function () {
        it('calculates stats correctly with data', function () {
            $startDate = Carbon::now()->subHours(2);
            $endDate = Carbon::now();

            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 100,
                'created_at' => $startDate->copy()->addHour(),
                'checked_at' => $startDate->copy()->addHour(),
            ]);
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 300,
                'created_at' => $startDate->copy()->addMinutes(90),
                'checked_at' => $startDate->copy()->addMinutes(90),
            ]);

            $stats = $this->service->getResponseTimeStats($this->monitor->id, $startDate, $endDate);

            expect($stats['avg'])->toBe(200.0); // (100 + 300) / 2
            expect($stats['min'])->toBe(100);
            expect($stats['max'])->toBe(300);
        });

        it('returns zeros when no data available', function () {
            $startDate = Carbon::now()->subHours(2);
            $endDate = Carbon::now();

            $stats = $this->service->getResponseTimeStats($this->monitor->id, $startDate, $endDate);

            expect($stats['avg'])->toBe(0);
            expect($stats['min'])->toBe(0);
            expect($stats['max'])->toBe(0);
        });

        it('excludes failed checks from stats', function () {
            $startDate = Carbon::now()->subHours(2);
            $endDate = Carbon::now();

            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'response_time' => 200,
                'created_at' => $startDate->copy()->addHour(),
                'checked_at' => $startDate->copy()->addHour(),
            ]);
            MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'response_time' => 5000, // This should be excluded
                'created_at' => $startDate->copy()->addMinutes(90),
                'checked_at' => $startDate->copy()->addMinutes(90),
            ]);

            $stats = $this->service->getResponseTimeStats($this->monitor->id, $startDate, $endDate);

            expect($stats['avg'])->toBe(200.0);
            expect($stats['min'])->toBe(200);
            expect($stats['max'])->toBe(200);
        });
    });

    describe('calculatePercentile', function () {
        it('calculates percentile correctly', function () {
            $sortedArray = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('calculatePercentile');
            $method->setAccessible(true);

            $p50 = $method->invoke($this->service, $sortedArray, 50);
            $p95 = $method->invoke($this->service, $sortedArray, 95);
            $p99 = $method->invoke($this->service, $sortedArray, 99);

            expect($p50)->toBe(55.0); // Between 50 and 60
            expect($p95)->toBe(95.5); // Between 90 and 100
            expect($p99)->toBe(99.1); // Close to 100
        });

        it('handles single value array', function () {
            $sortedArray = [100];

            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('calculatePercentile');
            $method->setAccessible(true);

            $p95 = $method->invoke($this->service, $sortedArray, 95);

            expect($p95)->toBe(100.0);
        });
    });
});
