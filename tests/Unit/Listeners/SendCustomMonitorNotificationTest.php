<?php

use App\Listeners\SendCustomMonitorNotification;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Helpers\Period;

beforeEach(function () {
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    $this->user1 = User::factory()->create();
    $this->listener = new SendCustomMonitorNotification;

    Notification::fake();
    Cache::flush();
});

describe('SendCustomMonitorNotification', function () {
    it('buffers notifications to cache', function () {
        $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

        $downtimePeriod = new Period(now()->subMinutes(5), now());
        $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

        $this->listener->handle($event);

        $pending = Cache::get('pending_monitor_notifications');
        expect($pending)->toHaveCount(1);
        expect($pending[0]['monitor_id'])->toBe($this->monitor->id);
    });

    it('skips buffering for monitors in maintenance', function () {
        $this->monitor->update([
            'maintenance_windows' => [
                [
                    'type' => 'one_time',
                    'start' => now()->subHour()->toIso8601String(),
                    'end' => now()->addHour()->toIso8601String(),
                ],
            ],
            'is_in_maintenance' => true,
            'maintenance_ends_at' => now()->addHour(),
        ]);

        $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

        $downtimePeriod = new Period(now()->subMinutes(5), now());
        $this->listener->handle(new UptimeCheckFailed($this->monitor, $downtimePeriod));

        expect(Cache::get('pending_monitor_notifications'))->toBeNull();
    });
});
