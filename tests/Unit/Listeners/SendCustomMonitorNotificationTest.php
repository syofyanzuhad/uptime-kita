<?php

use App\Listeners\SendCustomMonitorNotification;
use App\Models\Monitor;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();

    $this->listener = new SendCustomMonitorNotification();

    Notification::fake();
});

describe('SendCustomMonitorNotification', function () {
    describe('handle', function () {
        it('sends notifications to active users for failed check', function () {
            // Associate users with monitor
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => true]);

            $event = new UptimeCheckFailed($this->monitor);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification, $channels) {
                $data = $notification->toArray(null);
                return $data['status'] === 'DOWN' &&
                       $data['url'] === 'https://example.com' &&
                       str_contains($data['message'], 'DOWN');
            });

            Notification::assertSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('sends notifications to active users for recovered check', function () {
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => true]);

            $event = new UptimeCheckRecovered($this->monitor);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification, $channels) {
                $data = $notification->toArray(null);
                return $data['status'] === 'UP' &&
                       $data['url'] === 'https://example.com' &&
                       str_contains($data['message'], 'UP');
            });

            Notification::assertSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('sends notifications to active users for successful check', function () {
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            $event = new UptimeCheckSucceeded($this->monitor);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification, $channels) {
                $data = $notification->toArray(null);
                return $data['status'] === 'UP';
            });
        });

        it('does not send notifications to inactive users', function () {
            // Associate users with monitor, but make them inactive
            $this->monitor->users()->attach($this->user1->id, ['is_active' => false]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => false]);

            $event = new UptimeCheckFailed($this->monitor);

            $this->listener->handle($event);

            Notification::assertNotSentTo($this->user1, MonitorStatusChanged::class);
            Notification::assertNotSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('only sends notifications to users associated with the monitor', function () {
            // Only associate one user with the monitor
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            // user2 is not associated

            $event = new UptimeCheckFailed($this->monitor);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class);
            Notification::assertNotSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('does not send notifications when no users are associated', function () {
            // No users associated with monitor
            $event = new UptimeCheckFailed($this->monitor);

            $this->listener->handle($event);

            Notification::assertNothingSent();
        });

        it('continues sending to other users if one fails', function () {
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => true]);

            // Create a spy for user1 that will be returned by the database query
            $spyUser = spy($this->user1);
            $spyUser->shouldReceive('notify')
                ->once()
                ->andThrow(new Exception('Notification failed'));

            // Mock the monitor's users relationship to return our spy
            $this->monitor->shouldReceive('users')
                ->andReturnSelf();
            $this->monitor->shouldReceive('where')
                ->with('user_monitor.is_active', true)
                ->andReturnSelf();
            $this->monitor->shouldReceive('get')
                ->andReturn(collect([$spyUser, $this->user2]));

            $event = new UptimeCheckFailed($this->monitor);

            $this->listener->handle($event);

            // user2 should still receive notification despite user1 failing
            Notification::assertSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('determines correct status for failed event type', function () {
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            // Test failed event
            $failedEvent = new UptimeCheckFailed($this->monitor);
            $this->listener->handle($failedEvent);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                return $notification->toArray(null)['status'] === 'DOWN';
            });
        });

        it('determines correct status for recovered event type', function () {
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            // Test recovered event
            $recoveredEvent = new UptimeCheckRecovered($this->monitor);
            $this->listener->handle($recoveredEvent);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                return $notification->toArray(null)['status'] === 'UP';
            });
        });

        it('includes correct monitor information in notification', function () {
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            $event = new UptimeCheckFailed($this->monitor);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification, $channels) {
                $data = $notification->toArray(null);
                return $data['id'] === $this->monitor->id &&
                       $data['url'] === $this->monitor->url &&
                       $data['status'] === 'DOWN' &&
                       str_contains($data['message'], $this->monitor->url) &&
                       str_contains($data['message'], 'DOWN');
            });
        });
    });
});
