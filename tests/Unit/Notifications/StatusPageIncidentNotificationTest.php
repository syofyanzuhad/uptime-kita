<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Notifications\StatusPageIncidentNotification;

describe('StatusPageIncidentNotification', function () {
    beforeEach(function () {
        $this->statusPage = StatusPage::factory()->create([
            'title' => 'ACME Cloud',
            'path' => 'acme-cloud',
        ]);
        $this->subscriber = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'subscriber@acme.com',
            'unsubscribe_token' => 'unsub-token-abc',
            'verified_at' => now(),
        ]);
        $this->monitor = Monitor::factory()->create([
            'display_name' => 'API Gateway',
            'url' => 'https://api.example.com',
        ]);
    });

    it('uses mail channel', function () {
        $notification = new StatusPageIncidentNotification($this->subscriber, $this->monitor, 'down', 'Connection timeout');
        expect($notification->via(new stdClass))->toBe(['mail']);
    });

    it('formats down incident email with failure reason and unsubscribe link', function () {
        $notification = new StatusPageIncidentNotification($this->subscriber, $this->monitor, 'down', 'Connection timeout');
        $mail = $notification->toMail(new stdClass);

        expect($mail->subject)->toBe('[ACME Cloud] Incident Reported: API Gateway');
        expect($mail->introLines)->toContain('An incident has been detected on **API Gateway**.');
        expect($mail->introLines)->toContain('Status: **DOWN**');
        expect($mail->introLines)->toContain('Reason: Connection timeout');
        expect($mail->actionText)->toBe('View Status Page');
        expect($mail->outroLines)->toContain('[Unsubscribe from updates]('.url('/status-subscription/unsubscribe/unsub-token-abc').')');
    });

    it('formats up recovery email properly', function () {
        $notification = new StatusPageIncidentNotification($this->subscriber, $this->monitor, 'up');
        $mail = $notification->toMail(new stdClass);

        expect($mail->subject)->toBe('[ACME Cloud] Service Resolved: API Gateway');
        expect($mail->introLines)->toContain('**API Gateway** has recovered and is operating normally.');
        expect($mail->introLines)->toContain('Status: **OPERATIONAL**');
    });
});
