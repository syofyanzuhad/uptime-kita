<?php

use App\Models\Monitor;
use App\Models\MonitorPerformanceHourly;

describe('MonitorPerformanceHourly Model', function () {
    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $monitor = Monitor::factory()->create();

            $attributes = [
                'monitor_id' => $monitor->id,
                'hour' => now()->startOfHour(),
                'avg_response_time' => 250.5,
                'p95_response_time' => 400.25,
                'p99_response_time' => 500.75,
                'success_count' => 50,
                'failure_count' => 2,
            ];

            $performance = MonitorPerformanceHourly::create($attributes);

            expect($performance->monitor_id)->toBe($monitor->id);
            expect($performance->hour->format('Y-m-d H:i:s'))->toBe($attributes['hour']->format('Y-m-d H:i:s'));
            expect($performance->avg_response_time)->toBe(250.5);
            expect($performance->p95_response_time)->toBe(400.25);
            expect($performance->p99_response_time)->toBe(500.75);
            expect($performance->success_count)->toBe(50);
            expect($performance->failure_count)->toBe(2);
        });
    });

    describe('casts', function () {
        it('casts hour to datetime', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'hour' => '2024-01-01 15:00:00',
            ]);

            expect($performance->hour)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($performance->hour->format('Y-m-d H:i:s'))->toBe('2024-01-01 15:00:00');
        });

        it('casts response times to float', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'avg_response_time' => '250.5',
                'p95_response_time' => '400.25',
                'p99_response_time' => '500.75',
            ]);

            expect($performance->avg_response_time)->toBeFloat();
            expect($performance->avg_response_time)->toBe(250.5);
            expect($performance->p95_response_time)->toBeFloat();
            expect($performance->p95_response_time)->toBe(400.25);
            expect($performance->p99_response_time)->toBeFloat();
            expect($performance->p99_response_time)->toBe(500.75);
        });

        it('casts counts to integer', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => '50',
                'failure_count' => '2',
            ]);

            expect($performance->success_count)->toBeInt();
            expect($performance->success_count)->toBe(50);
            expect($performance->failure_count)->toBeInt();
            expect($performance->failure_count)->toBe(2);
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            $monitor = Monitor::factory()->create();
            $performance = MonitorPerformanceHourly::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($performance->monitor)->toBeInstanceOf(Monitor::class);
            expect($performance->monitor->id)->toBe($monitor->id);
        });
    });

    describe('getUptimePercentageAttribute', function () {
        it('calculates uptime percentage correctly', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 48,
                'failure_count' => 2,
            ]);

            $uptimePercentage = $performance->uptime_percentage;

            expect($uptimePercentage)->toBeFloat();
            expect($uptimePercentage)->toBe(96.0); // 48/50 * 100 = 96%
        });

        it('returns 100% when no failures', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 60,
                'failure_count' => 0,
            ]);

            expect($performance->uptime_percentage)->toBe(100.0);
        });

        it('returns 0% when all failures', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 0,
                'failure_count' => 60,
            ]);

            expect($performance->uptime_percentage)->toBe(0.0);
        });

        it('returns 100% when no checks at all', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 0,
                'failure_count' => 0,
            ]);

            expect($performance->uptime_percentage)->toBe(100.0);
        });

        it('rounds to 2 decimal places', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 33,
                'failure_count' => 67, // 33/100 = 33%
            ]);

            expect($performance->uptime_percentage)->toBe(33.0);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            $performance = new MonitorPerformanceHourly;
            expect($performance->getTable())->toBe('monitor_performance_hourly');
        });

        it('uses factory trait', function () {
            expect(class_uses(MonitorPerformanceHourly::class))->toContain(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
        });

        it('handles timestamps', function () {
            $performance = MonitorPerformanceHourly::factory()->create();

            expect($performance->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($performance->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('performance metrics', function () {
        it('can store zero response times', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'avg_response_time' => 0,
                'p95_response_time' => 0,
                'p99_response_time' => 0,
            ]);

            expect($performance->avg_response_time)->toBe(0.0);
            expect($performance->p95_response_time)->toBe(0.0);
            expect($performance->p99_response_time)->toBe(0.0);
        });

        it('maintains logical percentile relationships', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'avg_response_time' => 200.0,
                'p95_response_time' => 400.0,
                'p99_response_time' => 600.0,
            ]);

            // Generally, avg should be less than p95, p95 less than p99
            expect($performance->avg_response_time)->toBeLessThanOrEqual($performance->p95_response_time);
            expect($performance->p95_response_time)->toBeLessThanOrEqual($performance->p99_response_time);
        });

        it('can store high response times', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'avg_response_time' => 5000.0,
                'p95_response_time' => 10000.0,
                'p99_response_time' => 15000.0,
            ]);

            expect($performance->avg_response_time)->toBe(5000.0);
            expect($performance->p95_response_time)->toBe(10000.0);
            expect($performance->p99_response_time)->toBe(15000.0);
        });
    });

    describe('hourly data handling', function () {
        it('can store different hourly periods', function () {
            $currentHour = MonitorPerformanceHourly::factory()->create([
                'hour' => now()->startOfHour(),
            ]);

            $lastHour = MonitorPerformanceHourly::factory()->create([
                'hour' => now()->subHour()->startOfHour(),
            ]);

            $yesterday = MonitorPerformanceHourly::factory()->create([
                'hour' => now()->subDay()->startOfHour(),
            ]);

            expect($currentHour->hour->format('Y-m-d H'))->toBe(now()->startOfHour()->format('Y-m-d H'));
            expect($lastHour->hour->format('Y-m-d H'))->toBe(now()->subHour()->startOfHour()->format('Y-m-d H'));
            expect($yesterday->hour->format('Y-m-d H'))->toBe(now()->subDay()->startOfHour()->format('Y-m-d H'));
        });
    });

    describe('count handling', function () {
        it('can store large counts', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 3600, // One check per second
                'failure_count' => 100,
            ]);

            expect($performance->success_count)->toBe(3600);
            expect($performance->failure_count)->toBe(100);
        });

        it('can store zero counts', function () {
            $performance = MonitorPerformanceHourly::factory()->create([
                'success_count' => 0,
                'failure_count' => 0,
            ]);

            expect($performance->success_count)->toBe(0);
            expect($performance->failure_count)->toBe(0);
        });
    });
});
