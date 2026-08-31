<?php

use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use App\Notifications\VerifyStatusPageSubscriptionNotification;

describe('VerifyStatusPageSubscriptionNotification', function () {
    beforeEach(function () {
        $this->statusPage = StatusPage::factory()->create([
            'title' => 'ACME Services',
            'path' => 'acme-services',
        ]);
        $this->subscriber = StatusPageSubscriber::create([
            'status_page_id' => $this->statusPage->id,
            'email' => 'user@acme.com',
            'verification_token' => 'verify-token-123',
        ]);
        $this->notification = new VerifyStatusPageSubscriptionNotification($this->subscriber);
    });

    it('uses mail delivery channel', function () {
        $channels = $this->notification->via(new stdClass);
        expect($channels)->toBe(['mail']);
    });

    it('generates correct email representation with verification link', function () {
        $mail = $this->notification->toMail(new stdClass);

        expect($mail->subject)->toBe('Confirm your subscription to ACME Services');
        expect($mail->actionText)->toBe('Confirm Subscription');
        expect($mail->actionUrl)->toContain('/status-subscription/verify/verify-token-123');
        expect($mail->introLines)->toContain('You requested to receive email notifications when the status of services on **ACME Services** changes.');
    });
});
