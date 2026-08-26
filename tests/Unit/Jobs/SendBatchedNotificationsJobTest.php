<?php

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

beforeEach(function () {
    Notification::fake();
    Cache::flush();
});

describe('SendBatchedNotificationsJob', function () {
    describe('handle', function () {
        it('does nothing when there are no pending events', function () {
            Cache::flush();

            (new SendBatchedNotificationsJob)->handle();

            Notification::assertNothingSent();
        });

        it('logs a warning when user is not found', function () {
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
                ->with('SendBatchedNotificationsJob: User not found', Mockery::type('array'));

            (new SendBatchedNotificationsJob)->handle();
        });

        it('logs error when notification send fails', function () {
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
                ->andThrow(new RuntimeException('channel failed'));

            Log::shouldReceive('error')
                ->once()
                ->with('Failed to send batched notification to user '.$user->id, Mockery::type('array'));

            (new SendBatchedNotificationsJob)->handle();
        });

        it('sends batched notifications to multiple users', function () {
            $user = User::factory()->create();

            NotificationChannel::factory()->create([
                'user_id' => $user->id,
                'type' => 'email',
                'destination' => 'test@example.com',
                'is_enabled' => true,
            ]);

            $monitor = Monitor::factory()->create(['url' => 'https://site1.com']);
            $monitor->users()->attach($user->id, ['is_active' => true]);

            $listener = new SendCustomMonitorNotification;
            $event = new UptimeCheckFailed($monitor, new Period(now()->subMinutes(5), now()));
            $listener->handle($event);

            (new SendBatchedNotificationsJob)->handle();

            Notification::assertSentTo($user, BatchedMonitorStatusChanged::class);

            $this->assertNull(Cache::get('pending_monitor_notifications'));
        });
    });
});
