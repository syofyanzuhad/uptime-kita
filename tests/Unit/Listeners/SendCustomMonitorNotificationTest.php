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
use Spatie\UptimeMonitor\Helpers\Period;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();

    $this->listener = new SendCustomMonitorNotification;

    Notification::fake();
    \Illuminate\Support\Facades\Queue::fake();
});

describe('SendCustomMonitorNotification', function () {
    describe('handle', function () {
        it('sends notifications to active users for failed check', function () {
            // Create notification channels for users
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user2->id,
                'type' => 'email',
                'destination' => 'user2@example.com',
                'is_enabled' => true,
            ]);

            // Associate users with monitor
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => true]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class);

            Notification::assertSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('sends notifications to active users for recovered check', function () {
            // Create notification channels for users
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user2->id,
                'type' => 'email',
                'destination' => 'user2@example.com',
                'is_enabled' => true,
            ]);

            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => true]);

            $downtimePeriod = new Period(now()->subMinutes(10), now()->subMinutes(5));
            $event = new UptimeCheckRecovered($this->monitor, $downtimePeriod);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                $data = $notification->toArray($this->user1);

                return $data['status'] === 'UP';
            });

            Notification::assertSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('sends notifications to active users for successful check', function () {
            // Create notification channel for user
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            $event = new UptimeCheckSucceeded($this->monitor);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                $data = $notification->toArray($this->user1);

                return $data['status'] === 'UP';
            });
        });

        it('does not send notifications to inactive users', function () {
            // Associate users with monitor, but make them inactive
            $this->monitor->users()->attach($this->user1->id, ['is_active' => false]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => false]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

            $this->listener->handle($event);

            Notification::assertNotSentTo($this->user1, MonitorStatusChanged::class);
            Notification::assertNotSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('only sends notifications to users associated with the monitor', function () {
            // Create notification channel for user1 only
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            // Only associate one user with the monitor
            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            // user2 is not associated

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class);
            Notification::assertNotSentTo($this->user2, MonitorStatusChanged::class);
        });

        it('does not send notifications when no users are associated', function () {
            // No users associated with monitor
            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

            $this->listener->handle($event);

            Notification::assertNothingSent();
        });

        it('continues sending to other users if one fails', function () {
            // Create notification channels for users
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user2->id,
                'type' => 'email',
                'destination' => 'user2@example.com',
                'is_enabled' => true,
            ]);

            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);
            $this->monitor->users()->attach($this->user2->id, ['is_active' => true]);

            // Mock user1's notify method to throw exception
            Notification::shouldReceive('send')
                ->withArgs(function ($notifiables, $notification) {
                    return $notifiables instanceof \App\Models\User &&
                           $notifiables->id === $this->user1->id &&
                           $notification instanceof MonitorStatusChanged;
                })
                ->once()
                ->andThrow(new Exception('Notification failed'));

            // Allow other notifications to be sent normally
            Notification::shouldReceive('send')
                ->withArgs(function ($notifiables, $notification) {
                    return $notifiables instanceof \App\Models\User &&
                           $notifiables->id === $this->user2->id &&
                           $notification instanceof MonitorStatusChanged;
                })
                ->once()
                ->andReturnNull();

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

            // This should not throw exception despite user1 failing
            $this->listener->handle($event);

            // Since we're manually controlling the mocks, we can't use assertSentTo
            // Instead we verify via our mock expectations above
            expect(true)->toBeTrue();
        });

        it('determines correct status for failed event type', function () {
            // Create notification channel
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            // Test failed event
            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $failedEvent = new UptimeCheckFailed($this->monitor, $downtimePeriod);
            $this->listener->handle($failedEvent);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                return $notification->toArray($this->user1)['status'] === 'DOWN';
            });
        });

        it('determines correct status for recovered event type', function () {
            // Create notification channel
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            // Test recovered event
            $downtimePeriod = new Period(now()->subMinutes(10), now()->subMinutes(5));
            $recoveredEvent = new UptimeCheckRecovered($this->monitor, $downtimePeriod);
            $this->listener->handle($recoveredEvent);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                return $notification->toArray($this->user1)['status'] === 'UP';
            });
        });

        it('includes correct monitor information in notification', function () {
            // Create notification channel
            \App\Models\NotificationChannel::factory()->create([
                'user_id' => $this->user1->id,
                'type' => 'email',
                'destination' => 'user1@example.com',
                'is_enabled' => true,
            ]);

            $this->monitor->users()->attach($this->user1->id, ['is_active' => true]);

            $downtimePeriod = new Period(now()->subMinutes(5), now());
            $event = new UptimeCheckFailed($this->monitor, $downtimePeriod);

            $this->listener->handle($event);

            Notification::assertSentTo($this->user1, MonitorStatusChanged::class, function ($notification) {
                $data = $notification->toArray($this->user1);
                expect($data)->toHaveKeys(['id', 'url', 'status', 'message']);
                expect($data['id'])->toBe($this->monitor->id);
                expect($data['url'])->toBe((string) $this->monitor->url);
                expect($data['status'])->toBe('DOWN');
                expect($data['message'])->toContain((string) $this->monitor->url);
                expect($data['message'])->toContain('DOWN');

                return true;
            });
        });
    });
});
