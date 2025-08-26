<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MonitorHistory Model', function () {
    beforeEach(function () {
        Carbon::setTestNow(now());
        $this->monitor = Monitor::factory()->create();
        $this->history = MonitorHistory::factory()->create([
            'monitor_id' => $this->monitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'status_code' => 200,
        ]);
    });

    afterEach(function () {
        Carbon::setTestNow(null);
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $history = MonitorHistory::create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'message' => 'Connection timeout',
                'response_time' => 5000,
                'status_code' => 500,
                'checked_at' => now(),
            ]);

            expect($history->uptime_status)->toBe('down');
            expect($history->message)->toBe('Connection timeout');
            expect($history->response_time)->toBe(5000);
            expect($history->status_code)->toBe(500);
            expect($history->checked_at)->toBeInstanceOf(Carbon::class);
        });
    });

    describe('casts', function () {
        it('casts response_time to integer', function () {
            $history = MonitorHistory::factory()->create(['response_time' => '250.5']);

            expect($history->response_time)->toBeInt();
            expect($history->response_time)->toBe(250);
        });

        it('casts status_code to integer', function () {
            $history = MonitorHistory::factory()->create(['status_code' => '404']);

            expect($history->status_code)->toBeInt();
            expect($history->status_code)->toBe(404);
        });

        it('casts checked_at to datetime', function () {
            $history = MonitorHistory::factory()->create([
                'checked_at' => '2024-01-01 12:00:00',
            ]);

            expect($history->checked_at)->toBeInstanceOf(Carbon::class);
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            expect($this->history->monitor)->toBeInstanceOf(Monitor::class);
            expect($this->history->monitor->id)->toBe($this->monitor->id);
        });
    });

    describe('latestByMonitorId scope', function () {
        it('returns latest history for specific monitor', function () {
            // Clear existing history from beforeEach
            MonitorHistory::where('monitor_id', $this->monitor->id)->delete();
            
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(10),
                'uptime_status' => 'down',
            ]);

            $newerHistory = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(5),
                'uptime_status' => 'up',
            ]);

            $latest = MonitorHistory::latestByMonitorId($this->monitor->id)->first();

            expect($latest->id)->toBe($newerHistory->id);
            expect($latest->uptime_status)->toBe('up');
        });

        it('returns null when no history exists for monitor', function () {
            // Clean up all existing history first
            MonitorHistory::truncate();
            
            $otherMonitor = Monitor::factory()->create();

            $latest = MonitorHistory::latestByMonitorId($otherMonitor->id)->first();

            expect($latest)->toBeNull();
        });
    });

    describe('latestByMonitorIds scope', function () {
        it('returns latest history for multiple monitors', function () {
            // Clear existing history
            MonitorHistory::where('monitor_id', $this->monitor->id)->delete();
            
            $monitor2 = Monitor::factory()->create();
            
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(5),
                'uptime_status' => 'up',
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $monitor2->id,
                'created_at' => now()->subMinutes(3),
                'uptime_status' => 'down',
            ]);

            $latest = MonitorHistory::latestByMonitorIds([$this->monitor->id, $monitor2->id])->get();

            expect($latest)->toHaveCount(2);
            expect($latest->pluck('monitor_id')->toArray())->toContain($this->monitor->id, $monitor2->id);
        });

        it('returns empty collection for non-existent monitors', function () {
            $latest = MonitorHistory::latestByMonitorIds([999, 1000])->get();

            expect($latest)->toHaveCount(0);
        });
    });

    describe('getUniquePerMinute', function () {
        it('returns only latest record per minute', function () {
            // Clear existing history
            MonitorHistory::where('monitor_id', $this->monitor->id)->delete();
            
            $now = now();

            // Create multiple records within the same minute
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
                'uptime_status' => 'down',
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30),
                'uptime_status' => 'up',
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(50),
                'uptime_status' => 'recovery',
            ]);

            $unique = MonitorHistory::getUniquePerMinute($this->monitor->id)->get();

            // Should return 1 record: the latest from this minute
            expect($unique)->toHaveCount(1);
            
            // The record should be the latest one (recovery)
            expect($unique->first()->uptime_status)->toBe('recovery');
        });

        it('respects limit parameter', function () {
            // Create records in different minutes
            for ($i = 0; $i < 5; $i++) {
                MonitorHistory::factory()->create([
                    'monitor_id' => $this->monitor->id,
                    'created_at' => now()->subMinutes($i),
                ]);
            }

            $limited = MonitorHistory::getUniquePerMinute($this->monitor->id, 3)->get();

            expect($limited)->toHaveCount(3);
        });

        it('respects order parameters', function () {
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(10),
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(5),
            ]);

            $ascending = MonitorHistory::getUniquePerMinute($this->monitor->id, null, 'created_at', 'asc')->get();
            $descending = MonitorHistory::getUniquePerMinute($this->monitor->id, null, 'created_at', 'desc')->get();

            expect($ascending->first()->created_at->lt($ascending->last()->created_at))->toBeTrue();
            expect($descending->first()->created_at->gt($descending->last()->created_at))->toBeTrue();
        });
    });

    describe('prunable trait', function () {
        it('marks old records as prunable', function () {
            // Create old record
            $oldHistory = MonitorHistory::factory()->create([
                'created_at' => now()->subDays(31),
            ]);

            // Create recent record
            $recentHistory = MonitorHistory::factory()->create([
                'created_at' => now()->subDays(29),
            ]);

            // Call the prunable method on the model instance
            $prunableQuery = (new MonitorHistory())->prunable();
            $prunableIds = $prunableQuery->pluck('id');

            expect($prunableIds)->toContain($oldHistory->id);
            expect($prunableIds)->not->toContain($recentHistory->id);
        });

        it('has prunable method available', function () {
            expect(method_exists($this->history, 'prunable'))->toBeTrue();
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            expect($this->history->getTable())->toBe('monitor_histories');
        });

        it('uses factory and prunable traits', function () {
            expect(method_exists($this->history, 'factory'))->toBeTrue();
            expect(method_exists($this->history, 'prunable'))->toBeTrue();
        });

        it('handles timestamps', function () {
            expect($this->history->timestamps)->toBeTrue();
            expect($this->history->created_at)->not->toBeNull();
            expect($this->history->updated_at)->not->toBeNull();
        });
    });

    describe('status tracking', function () {
        it('tracks various uptime statuses', function () {
            $statuses = ['up', 'down', 'recovery', 'maintenance'];

            foreach ($statuses as $status) {
                $history = MonitorHistory::factory()->create([
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => $status,
                ]);

                expect($history->uptime_status)->toBe($status);
            }
        });

        it('stores response times correctly', function () {
            $responseTimes = [50, 250, 1000, 5000, null];

            foreach ($responseTimes as $time) {
                $history = MonitorHistory::factory()->create([
                    'monitor_id' => $this->monitor->id,
                    'response_time' => $time,
                ]);

                if ($time === null) {
                    expect($history->response_time)->toBeNull();
                } else {
                    expect($history->response_time)->toBe($time);
                }
            }
        });
    });
});