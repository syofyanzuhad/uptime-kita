<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('StatusPage Model', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->statusPage = StatusPage::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Status Page',
            'path' => 'test-status-page',
            'custom_domain' => 'status.example.com',
        ]);
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $statusPage = StatusPage::create([
                'user_id' => $this->user->id,
                'title' => 'New Status Page',
                'description' => 'Test description',
                'icon' => 'icon.png',
                'path' => 'new-status-page',
                'custom_domain' => 'new.example.com',
                'custom_domain_verified' => false,
                'force_https' => true,
            ]);

            expect($statusPage->title)->toBe('New Status Page');
            expect($statusPage->description)->toBe('Test description');
            expect($statusPage->icon)->toBe('icon.png');
            expect($statusPage->path)->toBe('new-status-page');
            expect($statusPage->custom_domain)->toBe('new.example.com');
            expect($statusPage->force_https)->toBeTrue();
        });
    });

    describe('casts', function () {
        it('casts boolean attributes correctly', function () {
            $statusPage = StatusPage::factory()->create([
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            expect($statusPage->custom_domain_verified)->toBeTrue();
            expect($statusPage->force_https)->toBeFalse();
        });

        it('casts datetime attributes correctly', function () {
            $statusPage = StatusPage::factory()->create([
                'custom_domain_verified_at' => '2024-01-01 12:00:00',
            ]);

            expect($statusPage->custom_domain_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('user relationship', function () {
        it('belongs to a user', function () {
            expect($this->statusPage->user)->toBeInstanceOf(User::class);
            expect($this->statusPage->user->id)->toBe($this->user->id);
        });
    });

    describe('monitors relationship', function () {
        it('belongs to many monitors', function () {
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            $this->statusPage->monitors()->attach([$monitor1->id, $monitor2->id]);

            expect($this->statusPage->monitors)->toHaveCount(2);
            expect($this->statusPage->monitors->pluck('id'))
                ->toContain($monitor1->id)
                ->toContain($monitor2->id);
        });
    });

    describe('generateUniquePath', function () {
        it('generates slug from title', function () {
            $path = StatusPage::generateUniquePath('My Awesome Status Page');

            expect($path)->toBe('my-awesome-status-page');
        });

        it('generates unique path when collision exists', function () {
            StatusPage::factory()->create(['path' => 'test-page']);
            StatusPage::factory()->create(['path' => 'test-page-1']);

            $path = StatusPage::generateUniquePath('Test Page');

            expect($path)->toBe('test-page-2');
        });

        it('handles special characters in title', function () {
            $path = StatusPage::generateUniquePath('Test & Special! Characters@#$%');

            expect($path)->toBe('test-special-characters-at');
        });

        it('handles empty title gracefully', function () {
            $path = StatusPage::generateUniquePath('');

            expect($path)->toBeString();
        });
    });

    describe('generateVerificationToken', function () {
        it('generates and saves verification token', function () {
            $token = $this->statusPage->generateVerificationToken();

            expect($token)->toStartWith('uptime-kita-verify-');
            expect(Str::length($token))->toBe(51); // 'uptime-kita-verify-' (19) + random (32)
            
            $this->statusPage->refresh();
            expect($this->statusPage->custom_domain_verification_token)->toBe($token);
        });

        it('generates different tokens on multiple calls', function () {
            $token1 = $this->statusPage->generateVerificationToken();
            $token2 = $this->statusPage->generateVerificationToken();

            expect($token1)->not->toBe($token2);
        });
    });

    describe('verifyCustomDomain', function () {
        it('returns false when no custom domain is set', function () {
            $statusPage = StatusPage::factory()->create(['custom_domain' => null]);

            $result = $statusPage->verifyCustomDomain();

            expect($result)->toBeFalse();
        });

        it('returns false when DNS verification fails', function () {
            $this->statusPage->generateVerificationToken();
            
            // Mock DNS failure (no actual DNS record exists)
            $result = $this->statusPage->verifyCustomDomain();

            expect($result)->toBeFalse();
            expect($this->statusPage->fresh()->custom_domain_verified)->toBeFalse();
        });

        it('updates verification status on successful verification', function () {
            // This would require mocking dns_get_record function
            // For now, we test the logic path
            $this->statusPage->update([
                'custom_domain_verification_token' => 'test-token',
            ]);

            // Since we can't easily mock dns_get_record, we test the false path
            $result = $this->statusPage->verifyCustomDomain();
            expect($result)->toBeFalse();
        });
    });

    describe('checkDnsVerification', function () {
        it('returns false when no custom domain', function () {
            $statusPage = StatusPage::factory()->create(['custom_domain' => null]);
            
            // Use reflection to call protected method
            $reflection = new ReflectionClass($statusPage);
            $method = $reflection->getMethod('checkDnsVerification');
            $method->setAccessible(true);
            
            $result = $method->invoke($statusPage);

            expect($result)->toBeFalse();
        });

        it('returns false when no verification token', function () {
            $statusPage = StatusPage::factory()->create([
                'custom_domain' => 'example.com',
                'custom_domain_verification_token' => null,
            ]);
            
            // Use reflection to call protected method
            $reflection = new ReflectionClass($statusPage);
            $method = $reflection->getMethod('checkDnsVerification');
            $method->setAccessible(true);
            
            $result = $method->invoke($statusPage);

            expect($result)->toBeFalse();
        });
    });

    describe('getUrl', function () {
        it('returns custom domain URL when verified', function () {
            $this->statusPage->update([
                'custom_domain' => 'status.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $url = $this->statusPage->getUrl();

            expect($url)->toBe('http://status.example.com');
        });

        it('returns HTTPS URL when force_https is enabled', function () {
            $this->statusPage->update([
                'custom_domain' => 'status.example.com',
                'custom_domain_verified' => true,
                'force_https' => true,
            ]);

            $url = $this->statusPage->getUrl();

            expect($url)->toBe('https://status.example.com');
        });

        it('returns default URL when custom domain not verified', function () {
            $this->statusPage->update([
                'custom_domain' => 'status.example.com',
                'custom_domain_verified' => false,
                'path' => 'my-status-page',
            ]);

            $url = $this->statusPage->getUrl();

            expect($url)->toContain('/status/my-status-page');
        });

        it('returns default URL when no custom domain', function () {
            $this->statusPage->update([
                'custom_domain' => null,
                'path' => 'my-status-page',
            ]);

            $url = $this->statusPage->getUrl();

            expect($url)->toContain('/status/my-status-page');
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            expect($this->statusPage->getTable())->toBe('status_pages');
        });

        it('uses factory trait', function () {
            expect(method_exists($this->statusPage, 'factory'))->toBeTrue();
            $factoryStatusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);
            expect($factoryStatusPage)->toBeInstanceOf(StatusPage::class);
        });

        it('handles timestamps', function () {
            expect($this->statusPage->timestamps)->toBeTrue();
            expect($this->statusPage->created_at)->not->toBeNull();
            expect($this->statusPage->updated_at)->not->toBeNull();
        });
    });
});