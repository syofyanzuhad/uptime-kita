<?php

use App\Listeners\BroadcastMonitorStatusChange;
use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Helpers\Period;

beforeEach(function () {
    Carbon::setTestNow(now());
    Cache::flush();
    $this->listener = new BroadcastMonitorStatusChange;
});

afterEach(function () {
    Carbon::setTestNow(null);
    Cache::flush();
});

describe('BroadcastMonitorStatusChange', function () {
    describe('handle', function () {
        it('broadcasts status change for public monitors on failure', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            expect($changes)->toHaveCount(1);
            expect($changes[0]['monitor_id'])->toBe($monitor->id);
            expect($changes[0]['new_status'])->toBe('down');
            expect($changes[0]['old_status'])->toBe('up');
        });

        it('broadcasts status change for public monitors on recovery', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            $downtimePeriod = new Period(now()->subMinutes(10), now()->subMinutes(5));
            $event = new UptimeCheckRecovered($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            expect($changes)->toHaveCount(1);
            expect($changes[0]['monitor_id'])->toBe($monitor->id);
            expect($changes[0]['new_status'])->toBe('up');
            expect($changes[0]['old_status'])->toBe('down');
        });

        it('does not broadcast for private monitors', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => false,
                'uptime_check_enabled' => true,
            ]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            expect($changes)->toBeEmpty();
        });

        it('includes status page ids in the broadcast', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            expect($changes[0])->toHaveKey('status_page_ids');
            expect($changes[0]['status_page_ids'])->toBeArray();
        });

        it('includes monitor name in the broadcast', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            expect($changes[0])->toHaveKey('monitor_name');
            expect($changes[0]['monitor_name'])->not->toBeEmpty();
        });

        it('keeps only last 100 entries', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            // Pre-fill cache with 100 entries
            $existingChanges = [];
            for ($i = 0; $i < 100; $i++) {
                $existingChanges[] = [
                    'id' => 'msc_'.$i,
                    'monitor_id' => 999,
                    'changed_at' => now()->subSeconds($i)->toIso8601String(),
                ];
            }
            Cache::put('monitor_status_changes', $existingChanges, now()->addMinutes(5));

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            expect(count($changes))->toBeLessThanOrEqual(100);
        });

        it('removes entries older than 5 minutes', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            // Pre-fill cache with old entries
            $existingChanges = [
                [
                    'id' => 'msc_old',
                    'monitor_id' => 999,
                    'changed_at' => now()->subMinutes(10)->toIso8601String(),
                ],
            ];
            Cache::put('monitor_status_changes', $existingChanges, now()->addMinutes(5));

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($monitor, $downtimePeriod);

            $this->listener->handle($event);

            $changes = Cache::get('monitor_status_changes', []);
            // Only new entry should remain, old one should be filtered
            expect($changes)->toHaveCount(1);
            expect($changes[0]['monitor_id'])->toBe($monitor->id);
        });
    });
});
