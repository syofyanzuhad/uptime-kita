<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendBatchedNotificationsJob;
use App\Listeners\SendCustomMonitorNotification;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\BatchedMonitorStatusChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function test_it_does_nothing_when_there_are_no_pending_events()
    {
        Cache::flush();

        (new SendBatchedNotificationsJob)->handle();

        Notification::assertNothingSent();
    }

    public function test_it_logs_a_warning_when_user_is_not_found()
    {
        Log::shouldReceive('info')->once();

        Cache::put('pending_monitor_notifications', [
            [
                'monitor_id' => 999,
                'url' => 'https://example.com',
                'status' => 'down',
                'user_ids' => [999999],
            ],
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('SendBatchedNotificationsJob: User not found', \Mockery::type('array'));

        (new SendBatchedNotificationsJob)->handle();
    }

    public function test_it_logs_error_when_notification_send_fails()
    {
        Log::shouldReceive('info')->once();

        $user = User::factory()->create();

        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'email',
            'destination' => 'test@example.com',
            'is_enabled' => true,
        ]);

        $monitor = Monitor::factory()->create(['url' => 'https://site1.com']);
        $monitor->users()->attach($user->id, ['is_active' => true]);

        Cache::put('pending_monitor_notifications', [
            [
                'monitor_id' => $monitor->id,
                'url' => $monitor->url,
                'status' => 'down',
                'user_ids' => [$user->id],
            ],
        ]);

        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('channel failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send batched notification to user '.$user->id, \Mockery::type('array'));

        (new SendBatchedNotificationsJob)->handle();
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
        $listener = new SendCustomMonitorNotification;
        $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
        $listener->handle($event);

        // Run the batch job
        (new SendBatchedNotificationsJob)->handle();

        // Verify notification was sent
        Notification::assertSentTo($user, BatchedMonitorStatusChanged::class);

        // Verify cache was cleared
        $this->assertNull(Cache::get('pending_monitor_notifications'));
    }
}
