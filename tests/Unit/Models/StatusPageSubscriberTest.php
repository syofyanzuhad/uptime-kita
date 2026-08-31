<?php

use App\Models\StatusPage;
use App\Models\StatusPageSubscriber;
use Carbon\Carbon;

describe('StatusPageSubscriber Model', function () {
    beforeEach(function () {
        $this->statusPage = StatusPage::factory()->create([
            'title' => 'Main Status Page',
            'path' => 'main-status-page',
        ]);
    });

    describe('creation and tokens', function () {
        it('automatically generates unsubscribe and verification tokens on create', function () {
            $subscriber = StatusPageSubscriber::create([
                'status_page_id' => $this->statusPage->id,
                'email' => 'subscriber@test.com',
            ]);

            expect($subscriber->unsubscribe_token)->not->toBeEmpty();
            expect($subscriber->verification_token)->not->toBeEmpty();
            expect($subscriber->verified_at)->toBeNull();
        });

        it('does not generate verification token if already verified', function () {
            $now = now();
            $subscriber = StatusPageSubscriber::create([
                'status_page_id' => $this->statusPage->id,
                'email' => 'verified@test.com',
                'verified_at' => $now,
            ]);

            expect($subscriber->unsubscribe_token)->not->toBeEmpty();
            expect($subscriber->verification_token)->toBeNull();
            expect($subscriber->verified_at)->toBeInstanceOf(Carbon::class);
        });
    });

    describe('relationships', function () {
        it('belongs to a status page', function () {
            $subscriber = StatusPageSubscriber::create([
                'status_page_id' => $this->statusPage->id,
                'email' => 'rel@test.com',
            ]);

            expect($subscriber->statusPage)->toBeInstanceOf(StatusPage::class);
            expect($subscriber->statusPage->id)->toBe($this->statusPage->id);
        });
    });

    describe('scopes and methods', function () {
        it('filters verified subscribers using scopeVerified', function () {
            $verified = StatusPageSubscriber::create([
                'status_page_id' => $this->statusPage->id,
                'email' => 'verified@test.com',
                'verified_at' => now(),
            ]);

            $unverified = StatusPageSubscriber::create([
                'status_page_id' => $this->statusPage->id,
                'email' => 'unverified@test.com',
            ]);

            $verifiedList = StatusPageSubscriber::verified()->pluck('id')->toArray();

            expect($verifiedList)->toContain($verified->id);
            expect($verifiedList)->not->toContain($unverified->id);
        });

        it('marks subscriber as verified', function () {
            $subscriber = StatusPageSubscriber::create([
                'status_page_id' => $this->statusPage->id,
                'email' => 'mark@test.com',
            ]);

            expect($subscriber->verified_at)->toBeNull();
            expect($subscriber->verification_token)->not->toBeNull();

            $subscriber->markAsVerified();

            expect($subscriber->fresh()->verified_at)->not->toBeNull();
            expect($subscriber->fresh()->verification_token)->toBeNull();
        });
    });
});
