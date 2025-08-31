<?php

use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('CustomDomainController', function () {
    describe('update method', function () {
        it('updates custom domain for status page owner', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => 'status.example.com',
                'force_https' => true,
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
            $response->assertSessionHas('flash.message', 'Custom domain updated. Please verify DNS settings.');

            $statusPage->refresh();
            expect($statusPage->custom_domain)->toBe('status.example.com');
            expect($statusPage->force_https)->toBeTrue();
            expect($statusPage->custom_domain_verified)->toBeFalse();
            expect($statusPage->custom_domain_verification_token)->not->toBeNull();
        });

        it('removes custom domain when set to null', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => 'old.example.com',
                'custom_domain_verification_token' => 'old-token',
            ]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => null,
                'force_https' => false,
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
            $response->assertSessionHas('flash.message', 'Custom domain removed.');

            $statusPage->refresh();
            expect($statusPage->custom_domain)->toBeNull();
            expect($statusPage->custom_domain_verification_token)->toBeNull();
            expect($statusPage->force_https)->toBeFalse();
        });

        it('rejects access for non-owner', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $owner->id]);

            $response = $this->actingAs($otherUser)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => 'status.example.com',
            ]);

            $response->assertForbidden();
        });

        it('validates domain format', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => 'invalid domain with spaces',
            ]);

            $response->assertSessionHasErrors(['custom_domain']);
        });

        it('validates domain uniqueness', function () {
            $user = User::factory()->create();
            $existingStatusPage = StatusPage::factory()->create(['custom_domain' => 'existing.example.com']);
            $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => 'existing.example.com',
            ]);

            $response->assertSessionHasErrors(['custom_domain']);
        });

        it('allows keeping same domain on update', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => 'status.example.com',
            ]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => 'status.example.com',
                'force_https' => false,
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
        });

        it('clears cache when domain is updated', function () {
            Cache::shouldReceive('forget')
                ->with('public_status_page_test-path')
                ->once();

            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'test-path',
            ]);

            $this->actingAs($user)->post("/status-pages/{$statusPage->id}/custom-domain", [
                'custom_domain' => 'status.example.com',
            ]);
        });
    });

    describe('verify method', function () {
        it('verifies custom domain when verification fails in test environment', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => 'status.example.com',
                'custom_domain_verification_token' => 'test-token',
            ]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/verify-domain");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Domain verification failed. Please check your DNS settings.');
        });

        it('returns error when verification fails without verification token', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => 'status.example.com',
                'custom_domain_verification_token' => null, // No token = will fail verification
            ]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/verify-domain");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Domain verification failed. Please check your DNS settings.');
        });

        it('returns error when no custom domain is configured', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => null,
            ]);

            $response = $this->actingAs($user)->post("/status-pages/{$statusPage->id}/verify-domain");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'No custom domain configured.');
        });

        it('rejects access for non-owner', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $owner->id,
                'custom_domain' => 'status.example.com',
            ]);

            $response = $this->actingAs($otherUser)->post("/status-pages/{$statusPage->id}/verify-domain");

            $response->assertForbidden();
        });
    });

    describe('dnsInstructions method', function () {
        it('returns DNS instructions for configured domain', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => 'status.example.com',
                'custom_domain_verification_token' => 'verification-token-123',
            ]);

            $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/dns-instructions");

            $response->assertOk();
            $response->assertJson([
                'domain' => 'status.example.com',
                'verification_token' => 'verification-token-123',
                'dns_records' => [
                    [
                        'type' => 'TXT',
                        'name' => '_uptime-kita.status.example.com',
                        'value' => 'verification-token-123',
                        'ttl' => 3600,
                    ],
                    [
                        'type' => 'CNAME',
                        'name' => 'status.example.com',
                        'value' => parse_url(config('app.url'), PHP_URL_HOST),
                        'ttl' => 3600,
                        'note' => 'Point your domain to our servers',
                    ],
                ],
            ]);
        });

        it('generates verification token if not exists', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => 'status.example.com',
                'custom_domain_verification_token' => null,
            ]);

            $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/dns-instructions");

            $response->assertOk();

            // Verify that a token was generated
            $statusPage->refresh();
            expect($statusPage->custom_domain_verification_token)->not->toBeNull();

            $response->assertJson([
                'domain' => 'status.example.com',
                'verification_token' => $statusPage->custom_domain_verification_token,
            ]);
        });

        it('returns error when no custom domain is configured', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'custom_domain' => null,
            ]);

            $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/dns-instructions");

            $response->assertStatus(400);
            $response->assertJson([
                'error' => 'No custom domain configured.',
            ]);
        });

        it('rejects access for non-owner', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $owner->id,
                'custom_domain' => 'status.example.com',
            ]);

            $response = $this->actingAs($otherUser)->get("/status-pages/{$statusPage->id}/dns-instructions");

            $response->assertForbidden();
        });
    });
});
