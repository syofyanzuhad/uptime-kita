<?php

use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;

describe('MonitorUptimeDaily Model', function () {
    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $monitor = Monitor::factory()->create();

            $attributes = [
                'monitor_id' => $monitor->id,
                'date' => '2024-01-01',
                'uptime_percentage' => 99.5,
                'avg_response_time' => 150.75,
                'min_response_time' => 50.25,
                'max_response_time' => 500.50,
                'total_checks' => 1440,
                'failed_checks' => 7,
            ];

            $uptimeDaily = MonitorUptimeDaily::create($attributes);

            expect($uptimeDaily->monitor_id)->toBe($monitor->id);
            expect($uptimeDaily->date->format('Y-m-d'))->toBe('2024-01-01');
            expect($uptimeDaily->uptime_percentage)->toBe(99.5);
            expect($uptimeDaily->avg_response_time)->toBe(150.75);
            expect($uptimeDaily->min_response_time)->toBe(50.25);
            expect($uptimeDaily->max_response_time)->toBe(500.50);
            expect($uptimeDaily->total_checks)->toBe(1440);
            expect($uptimeDaily->failed_checks)->toBe(7);
        });
    });

    describe('casts', function () {
        it('casts date to date', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'date' => '2024-01-15',
            ]);

            expect($uptimeDaily->date)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($uptimeDaily->date->format('Y-m-d'))->toBe('2024-01-15');
        });

        it('casts uptime_percentage to float', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'uptime_percentage' => 98.75,
            ]);

            expect($uptimeDaily->uptime_percentage)->toBeFloat();
            expect($uptimeDaily->uptime_percentage)->toBe(98.75);
        });

        it('casts response times to float', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'avg_response_time' => 150.25,
                'min_response_time' => 50.75,
                'max_response_time' => 500.50,
            ]);

            expect($uptimeDaily->avg_response_time)->toBeFloat();
            expect($uptimeDaily->min_response_time)->toBeFloat();
            expect($uptimeDaily->max_response_time)->toBeFloat();
            expect($uptimeDaily->avg_response_time)->toBe(150.25);
            expect($uptimeDaily->min_response_time)->toBe(50.75);
            expect($uptimeDaily->max_response_time)->toBe(500.50);
        });

        it('casts check counts to integer', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'total_checks' => 1440,
                'failed_checks' => 12,
            ]);

            expect($uptimeDaily->total_checks)->toBeInt();
            expect($uptimeDaily->failed_checks)->toBeInt();
            expect($uptimeDaily->total_checks)->toBe(1440);
            expect($uptimeDaily->failed_checks)->toBe(12);
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            $monitor = Monitor::factory()->create();
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($uptimeDaily->monitor)->toBeInstanceOf(Monitor::class);
            expect($uptimeDaily->monitor->id)->toBe($monitor->id);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            $uptimeDaily = new MonitorUptimeDaily;
            expect($uptimeDaily->getTable())->toBe('monitor_uptime_dailies');
        });

        it('uses factory trait', function () {
            expect(MonitorUptimeDaily::class)->toUse(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
        });

        it('handles timestamps', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create();

            expect($uptimeDaily->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($uptimeDaily->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('uptime calculations', function () {
        it('can store 100% uptime', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'uptime_percentage' => 100,
                'failed_checks' => 0,
                'total_checks' => 1440,
            ]);

            expect($uptimeDaily->uptime_percentage)->toBe(100.0);
            expect($uptimeDaily->failed_checks)->toBe(0);
        });

        it('can store 0% uptime', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'uptime_percentage' => 0,
                'failed_checks' => 1440,
                'total_checks' => 1440,
            ]);

            expect($uptimeDaily->uptime_percentage)->toBe(0.0);
            expect($uptimeDaily->failed_checks)->toBe(1440);
        });

        it('can store partial uptime', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'uptime_percentage' => 95.83,
                'failed_checks' => 60,
                'total_checks' => 1440,
            ]);

            expect($uptimeDaily->uptime_percentage)->toBe(95.83);
            expect($uptimeDaily->failed_checks)->toBe(60);
        });
    });

    describe('response time data', function () {
        it('can store null response times', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'avg_response_time' => null,
                'min_response_time' => null,
                'max_response_time' => null,
            ]);

            expect($uptimeDaily->avg_response_time)->toBeNull();
            expect($uptimeDaily->min_response_time)->toBeNull();
            expect($uptimeDaily->max_response_time)->toBeNull();
        });

        it('can store zero response times', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'avg_response_time' => 0.0,
                'min_response_time' => 0.0,
                'max_response_time' => 0.0,
            ]);

            expect($uptimeDaily->avg_response_time)->toBe(0.0);
            expect($uptimeDaily->min_response_time)->toBe(0.0);
            expect($uptimeDaily->max_response_time)->toBe(0.0);
        });

        it('maintains logical order of response times', function () {
            $uptimeDaily = MonitorUptimeDaily::factory()->create([
                'min_response_time' => 50.0,
                'avg_response_time' => 150.0,
                'max_response_time' => 500.0,
            ]);

            expect($uptimeDaily->min_response_time)->toBeLessThan($uptimeDaily->avg_response_time);
            expect($uptimeDaily->avg_response_time)->toBeLessThan($uptimeDaily->max_response_time);
        });
    });

    describe('date handling', function () {
        it('can store different dates', function () {
            $uptimeDaily1 = MonitorUptimeDaily::factory()->create([
                'date' => '2024-01-01',
            ]);
            $uptimeDaily2 = MonitorUptimeDaily::factory()->create([
                'date' => '2024-12-31',
            ]);

            expect($uptimeDaily1->date->format('Y-m-d'))->toBe('2024-01-01');
            expect($uptimeDaily2->date->format('Y-m-d'))->toBe('2024-12-31');
        });
    });
});
