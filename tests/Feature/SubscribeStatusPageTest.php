<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Notifications\StatusPageIncidentNotification;
use App\Notifications\VerifyStatusPageSubscriptionNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Helpers\Period;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

describe('SubscribeStatusPageTest', function () {
    beforeEach(function () {
        Notification::fake();
        $this->statusPage = StatusPage::factory()->create([
            'title' => 'Test Status Page',
            'path' => 'test-status-page',
        ]);
        $this->monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $this->statusPage->monitors()->attach($this->monitor->id);
    });

    it('allows a visitor to subscribe to a status page', function () {
        $response = postJson("/status/{$this->statusPage->path}/subscribe", [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message']);

        assertDatabaseHas('status_page_subscribers', [
            'status_page_id' => $this->statusPage->id,
            'email' => 'subscriber@example.com',
            'verified_at' => null,
        ]);

        Notification::assertSentOnDemand(
            VerifyStatusPageSubscriptionNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'subscriber@example.com'
        );
    });

    it('handles duplicate unverified subscriptions by re-sending verification email', function () {
        postJson("/status/{$this->statusPage->path}/subscribe", [
            'email' => 'subscriber@example.com',
        ]);

        $response = postJson("/status/{$this->statusPage->path}/subscribe", [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertOk();

        Notification::assertSentOnDemand(
            VerifyStatusPageSubscriptionNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'subscriber@example.com'
        );
    });

    it('handles duplicate verified subscription gracefully', function () {
        StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'verified@example.com',
            'verified_at' => now(),
        ]);

        $response = postJson("/status/{$this->statusPage->path}/subscribe", [
            'email' => 'verified@example.com',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'You are already subscribed to this status page.']);
    });

    it('verifies a subscriber via verification token', function () {
        $subscriber = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'verify@example.com',
            'verification_token' => 'test-verify-token',
        ]);

        $response = get('/status-subscription/verify/test-verify-token');

        $response->assertRedirect($this->statusPage->getUrl());

        expect($subscriber->fresh()->verified_at)->not->toBeNull();
        expect($subscriber->fresh()->verification_token)->toBeNull();
    });

    it('unsubscribes a subscriber via unsubscribe token', function () {
        $subscriber = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'unsub@example.com',
            'unsubscribe_token' => 'test-unsub-token',
            'verified_at' => now(),
        ]);

        $response = get('/status-subscription/unsubscribe/test-unsub-token');

        $response->assertRedirect($this->statusPage->getUrl());

        assertDatabaseMissing('status_page_subscribers', [
            'id' => $subscriber->id,
        ]);
    });

    it('sends notifications to verified subscribers when monitor fails and recovers', function () {
        $verifiedSubscriber = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'verified-sub@example.com',
            'verified_at' => now(),
        ]);

        $unverifiedSubscriber = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'unverified-sub@example.com',
            'verified_at' => null,
        ]);

        // Fire check failed event
        $downtimePeriod = new Period(now()->subMinute(), now());
        $eventFailed = new UptimeCheckFailed($this->monitor, $downtimePeriod);

        event($eventFailed);

        Notification::assertSentOnDemand(
            StatusPageIncidentNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'verified-sub@example.com'
        );

        Notification::assertSentOnDemandTimes(StatusPageIncidentNotification::class, 1);
    });
});
