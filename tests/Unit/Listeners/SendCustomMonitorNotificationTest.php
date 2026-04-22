<?php

namespace Tests\Unit\Listeners;

use App\Listeners\SendCustomMonitorNotification;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Helpers\Period;
use Tests\TestCase;

class SendCustomMonitorNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->monitor = Monitor::factory()->create([
            'url' => 'https://example.com',
            'uptime_check_enabled' => true,
        ]);

        $this->user1 = User::factory()->create();
        $this->listener = new SendCustomMonitorNotification;

        Notification::fake();
        Cache::flush();
    }

    public function test_it_buffers_notifications_to_cache()
    {
        $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

        $downtimePeriod = new Period(now()->subMinutes(5), now());
        $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

        $this->listener->handle($event);

        $pending = Cache::get('pending_monitor_notifications');
        $this->assertCount(1, $pending);
        $this->assertEquals($this->monitor->id, $pending[0]['monitor_id']);
    }

    public function test_it_skips_buffering_for_monitors_in_maintenance()
    {
        // Set a valid maintenance window that covers 'now'
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

        $this->assertNull(Cache::get('pending_monitor_notifications'));
    }
}
