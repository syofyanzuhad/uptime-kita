<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendBatchedNotificationsJob;
use App\Listeners\SendCustomMonitorNotification;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\BatchedMonitorStatusChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Helpers\Period;
use Tests\TestCase;

class SendBatchedNotificationsJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Cache::flush();
    }

    public function test_it_sends_batched_notifications_to_multiple_users()
    {
        $user = User::factory()->create();
        
        // Create an enabled notification channel so via() returns something
        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'email',
            'destination' => 'test@example.com',
            'is_enabled' => true,
        ]);

        $monitor = Monitor::factory()->create(['url' => 'https://site1.com']);
        $monitor->users()->attach($user->id, ['is_active' => true]);

        // Use the actual listener to buffer a notification
        $listener = new SendCustomMonitorNotification();
        $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
        $listener->handle($event);

        // Run the batch job
        (new SendBatchedNotificationsJob())->handle();

        // Verify notification was sent
        Notification::assertSentTo($user, BatchedMonitorStatusChanged::class);
        
        // Verify cache was cleared
        $this->assertNull(Cache::get('pending_monitor_notifications'));
    }
}
