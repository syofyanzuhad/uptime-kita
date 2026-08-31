<?php

use App\Listeners\NotifyStatusPageSubscribersOnStatusChange;
use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Notifications\StatusPageIncidentNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;
use Spatie\UptimeMonitor\Helpers\Period;

describe('NotifyStatusPageSubscribersOnStatusChange', function () {
    beforeEach(function () {
        Notification::fake();
        $this->listener = new NotifyStatusPageSubscribersOnStatusChange;
        $this->monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $this->statusPage = StatusPage::factory()->create();
        $this->statusPage->monitors()->attach($this->monitor->id);
    });

    it('ignores irrelevant events like UptimeCheckSucceeded', function () {
        $event = new UptimeCheckSucceeded($this->monitor);
        $this->listener->handle($event);

        Notification::assertNothingSent();
    });

    it('does nothing if monitor is in maintenance', function () {
        $this->monitor->update([
            'is_in_maintenance' => true,
            'maintenance_starts_at' => now()->subHour(),
            'maintenance_ends_at' => now()->addHour(),
        ]);

        $event = new UptimeCheckFailed($this->monitor, new Period(now()->subMinute(), now()));
        $this->listener->handle($event);

        Notification::assertNothingSent();
    });

    it('notifies only verified subscribers for failed and recovered checks', function () {
        $verified = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'verified@sub.com',
            'verified_at' => now(),
        ]);

        $unverified = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'unverified@sub.com',
            'verified_at' => null,
        ]);

        // Failed check
        $failedEvent = new UptimeCheckFailed($this->monitor, new Period(now()->subMinute(), now()));
        $this->listener->handle($failedEvent);

        Notification::assertSentOnDemand(
            StatusPageIncidentNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'verified@sub.com'
        );

        Notification::assertSentOnDemandTimes(StatusPageIncidentNotification::class, 1);

        // Recovered check
        $recoveredEvent = new UptimeCheckRecovered($this->monitor, new Period(now()->subMinute(), now()));
        $this->listener->handle($recoveredEvent);

        Notification::assertSentOnDemandTimes(StatusPageIncidentNotification::class, 2);
    });
});
