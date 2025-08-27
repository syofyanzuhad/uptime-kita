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
                'uptime_90d' => 95.25,
                'avg_response_time_24h' => 150,
                'min_response_time_24h' => 50,
                'max_response_time_24h' => 500,
                'incidents_24h' => 2,
                'incidents_7d' => 5,
                'incidents_30d' => 12,
                'total_checks_24h' => 1440,
                'total_checks_7d' => 10080,
                'total_checks_30d' => 43200,
                'recent_history_100m' => ['up', 'down', 'up', 'up'],
                'calculated_at' => now(),
            ];

            $statistic = MonitorStatistic::create($attributes);

            expect($statistic->monitor_id)->toBe($monitor->id);
            expect($statistic->uptime_1h)->toBe('99.50');
            expect($statistic->uptime_24h)->toBe('98.75');
            expect($statistic->avg_response_time_24h)->toBe(150);
            expect($statistic->recent_history_100m)->toBe(['up', 'down', 'up', 'up']);
        });
    });

    describe('casts', function () {
        it('casts uptime percentages to decimal', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_24h' => 99.5,
                'uptime_7d' => 98.75,
            ]);

            expect($statistic->uptime_24h)->toBeString();
            expect($statistic->uptime_24h)->toBe('99.50');
            expect($statistic->uptime_7d)->toBe('98.75');
        });

        it('casts recent_history_100m to array', function () {
            $history = ['up', 'down', 'recovery', 'up'];
            $statistic = MonitorStatistic::factory()->create([
                'recent_history_100m' => $history,
            ]);

            expect($statistic->recent_history_100m)->toBeArray();
            expect($statistic->recent_history_100m)->toBe($history);
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
            expect($uptimeStats)->toBe([
                '24h' => '99.50',
                '7d' => '98.75',
                '30d' => '97.25',
                '90d' => '96.50',
            ]);
        });
    });

    describe('getResponseTimeStatsAttribute', function () {
        it('returns response time stats in frontend format', function () {
            $statistic = MonitorStatistic::factory()->create([
                'avg_response_time_24h' => 150,
                'min_response_time_24h' => 50,
                'max_response_time_24h' => 500,
            ]);

            $responseStats = $statistic->response_time_stats;

            expect($responseStats)->toBeArray();
            expect($responseStats)->toBe([
                'average' => 150,
                'min' => 50,
                'max' => 500,
            ]);
        });
    });

    describe('isFresh method', function () {
        it('returns true when statistics are fresh', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => now()->subMinutes(30),
            ]);

            expect($statistic->isFresh())->toBeTrue();
        });

        it('returns false when statistics are old', function () {
            $statistic = MonitorStatistic::factory()->create([
                'calculated_at' => now()->subHours(2),
            ]);

            expect($statistic->isFresh())->toBeFalse();
        });

        it('returns false when calculated_at is very old', function () {
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
            expect(MonitorStatistic::class)->toUse(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
        });

        it('handles timestamps', function () {
            $statistic = MonitorStatistic::factory()->create();

            expect($statistic->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($statistic->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('statistical data', function () {
        it('can store zero values correctly', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_24h' => 0,
                'incidents_24h' => 0,
                'avg_response_time_24h' => null,
            ]);

            expect($statistic->uptime_24h)->toBe('0.00');
            expect($statistic->incidents_24h)->toBe(0);
            expect($statistic->avg_response_time_24h)->toBeNull();
        });

        it('can store 100% uptime values', function () {
            $statistic = MonitorStatistic::factory()->create([
                'uptime_1h' => 100.00,
                'uptime_24h' => 100.00,
                'uptime_7d' => 100.00,
                'uptime_30d' => 100.00,
                'uptime_90d' => 100.00,
            ]);

            expect($statistic->uptime_1h)->toBe('100.00');
            expect($statistic->uptime_24h)->toBe('100.00');
            expect($statistic->uptime_7d)->toBe('100.00');
        });
    });
});
