<?php

use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(now());
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'is_public' => true,
        'uptime_status' => 'up',
        'certificate_expiration_date' => now()->addMonths(6),
        'uptime_check_interval_in_minutes' => 5,
        'uptime_check_failure_reason' => null,
    ]);
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('MonitorResource', function () {
    describe('toArray', function () {
        it('returns correct basic monitor data', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data['id'])->toBe($this->monitor->id);
            expect($data['url'])->toBe($this->monitor->raw_url);
            expect($data['uptime_status'])->toBe('up');
            expect($data['is_public'])->toBeTrue();
            expect($data['uptime_check_interval'])->toBe(5);
        });

        it('includes certificate expiration date', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data['certificate_expiration_date'])->toEqual($this->monitor->certificate_expiration_date);
        });

        it('includes subscription status', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('is_subscribed');
            expect($data['is_subscribed'])->toBeFalse();
        });

        it('includes failure reason when present', function () {
            $this->monitor->update(['uptime_check_failure_reason' => 'Connection timeout']);

            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data['uptime_check_failure_reason'])->toBe('Connection timeout');
        });

        it('includes created and updated timestamps', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data['created_at'])->toEqual($this->monitor->created_at);
            expect($data['updated_at'])->toEqual($this->monitor->updated_at);
        });
    });

    describe('getDownEventsCount', function () {
        it('returns 0 when no histories are loaded', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data['down_for_events_count'])->toBe(0);
        });

        it('counts down events when histories are loaded', function () {
            // Create some monitor histories
            MonitorHistory::factory()->count(3)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
            ]);

            MonitorHistory::factory()->count(2)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
            ]);

            // Load histories relationship
            $this->monitor->load('histories');

            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data['down_for_events_count'])->toBe(2);
        });
    });

    describe('getTodayUptimePercentage', function () {
        it('returns 0 when uptime daily is not loaded', function () {
            $resource = new MonitorResource($this->monitor);

            $reflection = new ReflectionClass($resource);
            $method = $reflection->getMethod('getTodayUptimePercentage');
            $method->setAccessible(true);

            $percentage = $method->invoke($resource);

            expect($percentage)->toBe(0.0);
        });

        it('returns uptime percentage when uptime daily is loaded', function () {
            // Create uptime daily record
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->monitor->id,
                'date' => now()->toDateString(),
                'uptime_percentage' => 95.5,
            ]);

            // Load uptimeDaily relationship
            $this->monitor->load('uptimeDaily');

            $resource = new MonitorResource($this->monitor);

            $reflection = new ReflectionClass($resource);
            $method = $reflection->getMethod('getTodayUptimePercentage');
            $method->setAccessible(true);

            $percentage = $method->invoke($resource);

            expect($percentage)->toBe(95.5);
        });

        it('returns 0 when uptime daily is loaded but null', function () {
            // Don't create uptime daily record, but load the relationship
            $this->monitor->load('uptimeDaily');

            $resource = new MonitorResource($this->monitor);

            $reflection = new ReflectionClass($resource);
            $method = $reflection->getMethod('getTodayUptimePercentage');
            $method->setAccessible(true);

            $percentage = $method->invoke($resource);

            expect($percentage)->toBe(0.0);
        });
    });

    describe('when loaded relationships', function () {
        it('includes histories when loaded', function () {
            MonitorHistory::factory()->count(3)->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
            ]);

            $this->monitor->load('histories');

            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('histories');
            expect($data['histories'])->toHaveCount(3);
        });

        it('includes latest history when loaded', function () {
            $latestHistory = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => now(),
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => now()->subHour(),
            ]);

            $this->monitor->load('latestHistory');

            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('latest_history');
            expect($data['latest_history']['uptime_status'])->toBe('up');
        });

        it('includes uptime daily data when loaded', function () {
            // Create uptime daily records with different dates
            for ($i = 0; $i < 7; $i++) {
                MonitorUptimeDaily::factory()->create([
                    'monitor_id' => $this->monitor->id,
                    'date' => now()->subDays($i)->format('Y-m-d'),
                ]);
            }

            $this->monitor->load('uptimesDaily');

            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('uptimes_daily');
            expect($data['uptimes_daily'])->toHaveCount(7);

            // Check structure of uptime daily data
            $firstUptime = $data['uptimes_daily'][0];
            expect($firstUptime)->toHaveKey('date');
            expect($firstUptime)->toHaveKey('uptime_percentage');
        });

        it('includes tags when loaded', function () {
            // Since Monitor uses HasTags trait, we can test tag inclusion
            $this->monitor->attachTag('production');
            $this->monitor->attachTag('critical');
            $this->monitor->load('tags');

            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('tags');
            expect($data['tags'])->toHaveCount(2);

            $firstTag = $data['tags'][0];
            expect($firstTag)->toHaveKey('id');
            expect($firstTag)->toHaveKey('name');
            expect($firstTag)->toHaveKey('type');
        });
    });

    describe('calculated fields', function () {
        it('includes down events count', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('down_for_events_count');
            expect($data['down_for_events_count'])->toBe(0);
        });

        it('includes today uptime percentage', function () {
            $resource = new MonitorResource($this->monitor);
            $data = $resource->toArray(request());

            expect($data)->toHaveKey('today_uptime_percentage');
            expect($data['today_uptime_percentage'])->toBe(0.0);
        });
    });
});
