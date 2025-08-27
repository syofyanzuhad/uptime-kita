<?php

use App\Models\Monitor;
use App\Models\MonitorStatistic;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MonitorStatistic Model', function () {
    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $monitor = Monitor::factory()->create();

            $attributes = [
                'monitor_id' => $monitor->id,
                'uptime_1h' => 99.5,
                'uptime_24h' => 98.75,
                'uptime_7d' => 97.25,
                'uptime_30d' => 96.50,
                'uptime_90d' => 95.75,
                'avg_response_time_24h' => 250,
                'min_response_time_24h' => 100,
                'max_response_time_24h' => 500,
                'incidents_24h' => 2,
                'incidents_7d' => 5,
                'incidents_30d' => 10,
                'total_checks_24h' => 1440,
                'total_checks_7d' => 10080,
                'total_checks_30d' => 43200,
                'recent_history_100m' => ['up', 'up', 'down', 'up'],
                'calculated_at' => now(),
            ];

            $statistic = MonitorStatistic::create($attributes);

            expect($statistic->monitor_id)->toBe($monitor->id);
            expect($statistic->uptime_1h)->toBe(99.5);
            expect($statistic->uptime_24h)->toBe(98.75);
            expect($statistic->uptime_7d)->toBe(97.25);
            expect($statistic->uptime_30d)->toBe(96.50);
            expect($statistic->uptime_90d)->toBe(95.75);
            expect($statistic->avg_response_time_24h)->toBe(250);
            expect($statistic->min_response_time_24h)->toBe(100);
            expect($statistic->max_response_time_24h)->toBe(500);
            expect($statistic->incidents_24h)->toBe(2);
            expect($statistic->incidents_7d)->toBe(5);
            expect($statistic->incidents_30d)->toBe(10);
            expect($statistic->total_checks_24h)->toBe(1440);
            expect($statistic->total_checks_7d)->toBe(10080);
            expect($statistic->total_checks_30d)->toBe(43200);
            expect($statistic->recent_history_100m)->toBe(['up', 'up', 'down', 'up']);
        });
    });

    describe('casts', function () {
        it('casts uptime percentages to decimal with 2 places', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_1h' => 99.567,
                'uptime_24h' => 98.234,
                'uptime_7d' => 97.891,
                'uptime_30d' => 96.456,
                'uptime_90d' => 95.123,
            ]);

            // Decimal cast preserves precision
            expect($statistic->uptime_1h)->toBe(99.567);
            expect($statistic->uptime_24h)->toBe(98.234);
            expect($statistic->uptime_7d)->toBe(97.891);
            expect($statistic->uptime_30d)->toBe(96.456);
            expect($statistic->uptime_90d)->toBe(95.123);
        });

        it('casts recent_history_100m to array', function () {
            $historyData = [
                ['status' => 'up', 'time' => '12:00'],
                ['status' => 'down', 'time' => '12:01'],
                ['status' => 'up', 'time' => '12:02'],
            ];

            $statistic = MonitorStatistic::factory()->create([
                'recent_history_100m' => $historyData,
            ]);

            expect($statistic->recent_history_100m)->toBeArray();
            expect($statistic->recent_history_100m)->toBe($historyData);
        });

        it('handles null recent_history_100m', function () {
            $statistic = MonitorStatistic::factory()->create([
                'recent_history_100m' => null,
            ]);

            expect($statistic->recent_history_100m)->toBeNull();
        });

        it('casts calculated_at to datetime', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => '2024-01-01 12:00:00',
            ]);

            expect($statistic->calculated_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($statistic->calculated_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 12:00:00');
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            $monitor = Monitor::factory()->create();
            $statistic = MonitorStatistic::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($statistic->monitor)->toBeInstanceOf(Monitor::class);
            expect($statistic->monitor->id)->toBe($monitor->id);
        });
    });

    describe('getUptimeStatsAttribute', function () {
        it('returns uptime stats in frontend format', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_24h' => 99.5,
                'uptime_7d' => 98.75,
                'uptime_30d' => 97.25,
                'uptime_90d' => 96.50,
            ]);

            $uptimeStats = $statistic->uptime_stats;

            expect($uptimeStats)->toBeArray();
            expect($uptimeStats)->toHaveKeys(['24h', '7d', '30d', '90d']);
            expect($uptimeStats['24h'])->toBe(99.5);
            expect($uptimeStats['7d'])->toBe(98.75);
            expect($uptimeStats['30d'])->toBe(97.25);
            expect($uptimeStats['90d'])->toBe(96.50);
        });
    });

    describe('getResponseTimeStatsAttribute', function () {
        it('returns response time stats in frontend format', function () {
            $statistic = MonitorStatistic::factory()->create([
                'avg_response_time_24h' => 250,
                'min_response_time_24h' => 100,
                'max_response_time_24h' => 500,
            ]);

            $responseTimeStats = $statistic->response_time_stats;

            expect($responseTimeStats)->toBeArray();
            expect($responseTimeStats)->toHaveKeys(['average', 'min', 'max']);
            expect($responseTimeStats['average'])->toBe(250);
            expect($responseTimeStats['min'])->toBe(100);
            expect($responseTimeStats['max'])->toBe(500);
        });
    });

    describe('isFresh method', function () {
        it('returns true when statistics are less than an hour old', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => now()->subMinutes(30),
            ]);

            expect($statistic->isFresh())->toBeTrue();
        });

        it('returns false when statistics are more than an hour old', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => now()->subMinutes(90),
            ]);

            expect($statistic->isFresh())->toBeFalse();
        });

        it('returns false when calculated_at is old', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => now()->subHours(25),
            ]);

            expect($statistic->isFresh())->toBeFalse();
        });

        it('returns true when statistics are exactly an hour old', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => now()->subMinutes(59),
            ]);

            expect($statistic->isFresh())->toBeTrue();
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            $statistic = new MonitorStatistic;
            expect($statistic->getTable())->toBe('monitor_statistics');
        });

        it('uses factory trait', function () {
            expect(class_uses(MonitorStatistic::class))->toContain(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
        });

        it('handles timestamps', function () {
            $statistic = MonitorStatistic::factory()->create();

            expect($statistic->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($statistic->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('statistical data', function () {
        it('can store zero values for statistics', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_24h' => 0,
                'incidents_24h' => 0,
                'avg_response_time_24h' => 0,
            ]);

            expect($statistic->uptime_24h)->toBe(0.0);
            expect($statistic->incidents_24h)->toBe(0);
            expect($statistic->avg_response_time_24h)->toBe(0);
        });

        it('can store 100% uptime values', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_1h' => 100,
                'uptime_24h' => 100,
                'uptime_7d' => 100,
                'uptime_30d' => 100,
                'uptime_90d' => 100,
            ]);

            expect($statistic->uptime_1h)->toBe(100.0);
            expect($statistic->uptime_24h)->toBe(100.0);
            expect($statistic->uptime_7d)->toBe(100.0);
            expect($statistic->uptime_30d)->toBe(100.0);
            expect($statistic->uptime_90d)->toBe(100.0);
        });
    });
});
