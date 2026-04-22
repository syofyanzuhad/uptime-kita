<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorPerformanceHourly;
use App\Services\MonitorPerformanceService;
use Carbon\Carbon;

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
            expect($performance->avg_response_time)->toBe(250.0);
        });

        it('updates existing hourly performance record with running average', function () {
            $hour = Carbon::now()->startOfHour();
            $existing = MonitorPerformanceHourly::create([
                'monitor_id' => $this->monitor->id,
                'hour' => $hour,
                'success_count' => 1,
                'avg_response_time' => 100,
                'failure_count' => 0,
            ]);

            // New ping is 300ms. Average of 100 and 300 is 200.
            $this->service->updateHourlyMetrics($this->monitor->id, 300, true);

            $existing->refresh();
            expect($existing->success_count)->toBe(2);
            expect($existing->avg_response_time)->toBe(200.0);
        });

        it('increments failure count for failed checks without affecting average', function () {
            $hour = Carbon::now()->startOfHour();
            $existing = MonitorPerformanceHourly::create([
                'monitor_id' => $this->monitor->id,
                'hour' => $hour,
                'success_count' => 1,
                'avg_response_time' => 100,
                'failure_count' => 0,
            ]);

            $this->service->updateHourlyMetrics($this->monitor->id, null, false);

            $existing->refresh();
            expect($existing->success_count)->toBe(1);
            expect($existing->failure_count)->toBe(1);
            expect($existing->avg_response_time)->toBe(100.0);
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
            expect((float)$metrics['avg_response_time'])->toBe(200.0); // (150 + 250) / 2
            expect($metrics['min_response_time'])->toBe(150);
            expect($metrics['max_response_time'])->toBe(250);
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

            expect((float)$stats['avg'])->toBe(200.0); // (100 + 300) / 2
            expect($stats['min'])->toBe(100);
            expect($stats['max'])->toBe(300);
        });
    });
});
