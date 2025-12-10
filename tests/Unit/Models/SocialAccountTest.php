<?php

use App\Models\SocialAccount;
use App\Models\User;

describe('SocialAccount Model', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->socialAccount = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'provider_id' => '12345',
            'provider_name' => 'github',
        ]);
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $account = SocialAccount::create([
                'user_id' => $this->user->id,
                'provider_id' => '67890',
                'provider_name' => 'google',
            ]);

            expect($account->user_id)->toBe($this->user->id);
            expect($account->provider_id)->toBe('67890');
            expect($account->provider_name)->toBe('google');
        });
    });

    describe('user relationship', function () {
        it('belongs to a user', function () {
            expect($this->socialAccount->user)->toBeInstanceOf(User::class);
            expect($this->socialAccount->user->id)->toBe($this->user->id);
        });
    });

    describe('provider support', function () {
        it('supports different social providers', function () {
            $providers = ['github', 'google', 'facebook', 'twitter', 'linkedin'];

            foreach ($providers as $provider) {
                $account = SocialAccount::factory()->create([
                    'user_id' => $this->user->id,
                    'provider_name' => $provider,
                    'provider_id' => 'test-id-'.$provider,
                ]);

                expect($account->provider_name)->toBe($provider);
                expect($account->provider_id)->toBe('test-id-'.$provider);
            }
        });
    });

    describe('unique provider accounts', function () {
        it('can have multiple accounts for different providers', function () {
            $githubAccount = SocialAccount::factory()->create([
                'user_id' => $this->user->id,
                'provider_name' => 'github',
                'provider_id' => 'github-123',
            ]);

            $googleAccount = SocialAccount::factory()->create([
                'user_id' => $this->user->id,
                'provider_name' => 'google',
                'provider_id' => 'google-456',
            ]);

            $userAccounts = $this->user->socialAccounts;

            expect($userAccounts)->toHaveCount(3); // Including the one from beforeEach
            expect($userAccounts->pluck('provider_name')->toArray())
                ->toContain('github')
                ->toContain('google');
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            expect($this->socialAccount->getTable())->toBe('social_accounts');
        });

        it('handles timestamps', function () {
            expect($this->socialAccount->timestamps)->toBeTrue();
            expect($this->socialAccount->created_at)->not->toBeNull();
            expect($this->socialAccount->updated_at)->not->toBeNull();
        });
    });

    describe('provider id formats', function () {
        it('handles numeric provider ids', function () {
            $account = SocialAccount::factory()->create([
                'provider_id' => '12345678901234567890', // Large numeric ID
            ]);

            expect($account->provider_id)->toBe('12345678901234567890');
        });

        it('handles string provider ids', function () {
            $account = SocialAccount::factory()->create([
                'provider_id' => 'user-uuid-string-id',
            ]);

            expect($account->provider_id)->toBe('user-uuid-string-id');
        });
    });
});
