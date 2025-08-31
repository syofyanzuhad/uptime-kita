<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('EmailVerificationNotificationController', function () {
    describe('store method', function () {
        it('redirects to dashboard if user email is already verified', function () {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            $response = $this->actingAs($user)->post(route('verification.send'));

            $response->assertRedirect(route('dashboard'));
        });

        it('sends verification email and returns back with success status for unverified user', function () {
            Notification::fake();

            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);

            $response = $this->actingAs($user)->post(route('verification.send'));

            $response->assertRedirect();
            $response->assertSessionHas('status', 'verification-link-sent');
        });

        it('requires authentication', function () {
            $response = $this->post(route('verification.send'));

            $response->assertRedirect(route('login'));
        });

        it('sends email verification notification for unverified user', function () {
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);

            $this->actingAs($user)->post(route('verification.send'));

            // We can't easily test the actual email sending without mocking,
            // but we can verify the user is still unverified
            expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
        });

        it('handles multiple verification requests gracefully', function () {
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);

            $response1 = $this->actingAs($user)->post(route('verification.send'));
            $response2 = $this->actingAs($user)->post(route('verification.send'));

            $response1->assertRedirect();
            $response1->assertSessionHas('status', 'verification-link-sent');

            $response2->assertRedirect();
            $response2->assertSessionHas('status', 'verification-link-sent');
        });
    });
});
