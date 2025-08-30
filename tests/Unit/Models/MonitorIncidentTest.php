<?php

use App\Models\Monitor;
use App\Models\MonitorIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MonitorIncident Model', function () {
    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $monitor = Monitor::factory()->create();

            $attributes = [
                'monitor_id' => $monitor->id,
                'type' => 'down',
                'started_at' => now(),
                'ended_at' => now()->addMinutes(30),
                'duration_minutes' => 30,
                'reason' => 'Connection timeout',
                'response_time' => 0,
                'status_code' => 500,
            ];

            $incident = MonitorIncident::create($attributes);

            expect($incident->monitor_id)->toBe($attributes['monitor_id']);
            expect($incident->type)->toBe($attributes['type']);
            expect($incident->reason)->toBe($attributes['reason']);
            expect($incident->response_time)->toBe($attributes['response_time']);
            expect($incident->status_code)->toBe($attributes['status_code']);
            expect($incident->duration_minutes)->toBe($attributes['duration_minutes']);
        });
    });

    describe('casts', function () {
        it('casts started_at to datetime', function () {
            $incident = MonitorIncident::factory()->create([
                'started_at' => '2024-01-01 12:00:00',
            ]);

            expect($incident->started_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($incident->started_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 12:00:00');
        });

        it('casts ended_at to datetime', function () {
            $incident = MonitorIncident::factory()->create([
                'ended_at' => '2024-01-01 13:00:00',
            ]);

            expect($incident->ended_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($incident->ended_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 13:00:00');
        });

        it('casts response_time to integer', function () {
            $incident = MonitorIncident::factory()->create([
                'response_time' => '250',
            ]);

            expect($incident->response_time)->toBeInt();
            expect($incident->response_time)->toBe(250);
        });

        it('casts status_code to integer', function () {
            $incident = MonitorIncident::factory()->create([
                'status_code' => '404',
            ]);

            expect($incident->status_code)->toBeInt();
            expect($incident->status_code)->toBe(404);
        });

        it('casts duration_minutes to integer', function () {
            $incident = MonitorIncident::factory()->create([
                'duration_minutes' => '45',
            ]);

            expect($incident->duration_minutes)->toBeInt();
            expect($incident->duration_minutes)->toBe(45);
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            $monitor = Monitor::factory()->create();
            $incident = MonitorIncident::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($incident->monitor)->toBeInstanceOf(Monitor::class);
            expect($incident->monitor->id)->toBe($monitor->id);
        });
    });

    describe('recent scope', function () {
        it('returns incidents from the last 7 days by default', function () {
            // Create old incident
            MonitorIncident::factory()->create([
                'started_at' => now()->subDays(10),
            ]);

            // Create recent incidents
            $recentIncident1 = MonitorIncident::factory()->create([
                'started_at' => now()->subDays(5),
            ]);
            $recentIncident2 = MonitorIncident::factory()->create([
                'started_at' => now()->subDays(2),
            ]);

            $recentIncidents = MonitorIncident::recent()->get();

            expect($recentIncidents)->toHaveCount(2);
            expect($recentIncidents->pluck('id')->toArray())->toContain($recentIncident1->id, $recentIncident2->id);
        });

        it('accepts custom days parameter', function () {
            // Create incidents
            MonitorIncident::factory()->create([
                'started_at' => now()->subDays(15),
            ]);
            MonitorIncident::factory()->create([
                'started_at' => now()->subDays(8),
            ]);
            $recentIncident = MonitorIncident::factory()->create([
                'started_at' => now()->subDays(3),
            ]);

            $recentIncidents = MonitorIncident::recent(5)->get();

            expect($recentIncidents)->toHaveCount(1);
            expect($recentIncidents->first()->id)->toBe($recentIncident->id);
        });

        it('orders incidents by started_at in descending order', function () {
            $incident1 = MonitorIncident::factory()->create([
                'started_at' => now()->subDays(3),
            ]);
            $incident2 = MonitorIncident::factory()->create([
                'started_at' => now()->subDay(),
            ]);
            $incident3 = MonitorIncident::factory()->create([
                'started_at' => now()->subHours(2),
            ]);

            $recentIncidents = MonitorIncident::recent()->get();

            expect($recentIncidents->first()->id)->toBe($incident3->id);
            expect($recentIncidents->last()->id)->toBe($incident1->id);
        });
    });

    describe('ongoing scope', function () {
        it('returns only incidents with null ended_at', function () {
            // Create ended incident
            MonitorIncident::factory()->create([
                'started_at' => now()->subHour(),
                'ended_at' => now(),
            ]);

            // Create ongoing incidents
            $ongoingIncident1 = MonitorIncident::factory()->create([
                'started_at' => now()->subHour(),
                'ended_at' => null,
            ]);
            $ongoingIncident2 = MonitorIncident::factory()->create([
                'started_at' => now()->subMinutes(30),
                'ended_at' => null,
            ]);

            $ongoingIncidents = MonitorIncident::ongoing()->get();

            expect($ongoingIncidents)->toHaveCount(2);
            expect($ongoingIncidents->pluck('id')->toArray())->toContain($ongoingIncident1->id, $ongoingIncident2->id);
        });
    });

    describe('endIncident method', function () {
        it('sets ended_at and calculates duration', function () {
            $startedAt = now()->subMinutes(45);
            $incident = MonitorIncident::factory()->create([
                'started_at' => $startedAt,
                'ended_at' => null,
                'duration_minutes' => null,
            ]);

            // Mock now() to ensure consistent duration
            \Carbon\Carbon::setTestNow(now());

            $incident->endIncident();

            expect($incident->ended_at)->not->toBeNull();
            expect($incident->ended_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($incident->duration_minutes)->toBeInt();
            expect($incident->duration_minutes)->toBe(45);

            // Verify it was saved
            $incident->refresh();
            expect($incident->ended_at)->not->toBeNull();
            expect($incident->duration_minutes)->toBe(45);

            \Carbon\Carbon::setTestNow(); // Reset
        });

        it('calculates correct duration for various time periods', function () {
            // Test 1 hour incident
            $incident = MonitorIncident::factory()->create([
                'started_at' => now()->subHour(),
                'ended_at' => null,
            ]);

            $incident->endIncident();
            expect($incident->duration_minutes)->toBe(60);

            // Test 24 hour incident
            $incident2 = MonitorIncident::factory()->create([
                'started_at' => now()->subDay(),
                'ended_at' => null,
            ]);

            $incident2->endIncident();
            expect($incident2->duration_minutes)->toBe(1440);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            $incident = new MonitorIncident;
            expect($incident->getTable())->toBe('monitor_incidents');
        });

        it('uses factory trait', function () {
            expect(class_uses(MonitorIncident::class))->toContain(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
        });

        it('handles timestamps', function () {
            $incident = MonitorIncident::factory()->create();

            expect($incident->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($incident->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('incident types', function () {
        it('can store different incident types', function () {
            $downIncident = MonitorIncident::factory()->create([
                'type' => 'down',
            ]);

            $degradedIncident = MonitorIncident::factory()->create([
                'type' => 'degraded',
            ]);

            $recoveredIncident = MonitorIncident::factory()->create([
                'type' => 'recovered',
            ]);

            expect($downIncident->type)->toBe('down');
            expect($degradedIncident->type)->toBe('degraded');
            expect($recoveredIncident->type)->toBe('recovered');
        });
    });
});
