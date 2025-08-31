<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

describe('SocialiteController', function () {
    describe('redirectToProvider method', function () {
        it('redirects to provider', function () {
            Socialite::shouldReceive('driver')
                ->with('github')
                ->once()
                ->andReturnSelf();

            Socialite::shouldReceive('redirect')
                ->once()
                ->andReturn(redirect('https://github.com/oauth'));

            $response = $this->get('/auth/github');

            $response->assertRedirect('https://github.com/oauth');
        });
    });

    describe('handleProvideCallback method', function () {
        it('logs in existing user with social account', function () {
            $user = User::factory()->create();
            $socialAccount = SocialAccount::factory()->create([
                'user_id' => $user->id,
                'provider_id' => '12345',
                'provider_name' => 'github',
            ]);

            $socialiteUser = mock(SocialiteUser::class);
            $socialiteUser->shouldReceive('getId')->andReturn('12345');
            $socialiteUser->shouldReceive('getEmail')->andReturn($user->email);
            $socialiteUser->shouldReceive('getName')->andReturn($user->name);

            Socialite::shouldReceive('driver')
                ->with('github')
                ->once()
                ->andReturnSelf();

            Socialite::shouldReceive('user')
                ->once()
                ->andReturn($socialiteUser);

            $response = $this->get('/auth/github/callback');

            $response->assertRedirect(route('home'));
            $this->assertAuthenticatedAs($user);
        });

        it('creates new user and social account for new social user', function () {
            $socialiteUser = mock(SocialiteUser::class);
            $socialiteUser->shouldReceive('getId')->andReturn('12345');
            $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
            $socialiteUser->shouldReceive('getName')->andReturn('John Doe');

            Socialite::shouldReceive('driver')
                ->with('github')
                ->once()
                ->andReturnSelf();

            Socialite::shouldReceive('user')
                ->once()
                ->andReturn($socialiteUser);

            $response = $this->get('/auth/github/callback');

            $response->assertRedirect(route('home'));

            $this->assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            $this->assertDatabaseHas('social_accounts', [
                'provider_id' => '12345',
                'provider_name' => 'github',
            ]);

            $user = User::where('email', 'john@example.com')->first();
            $this->assertAuthenticatedAs($user);
        });

        it('links social account to existing user with same email', function () {
            $user = User::factory()->create([
                'email' => 'john@example.com',
            ]);

            $socialiteUser = mock(SocialiteUser::class);
            $socialiteUser->shouldReceive('getId')->andReturn('12345');
            $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
            $socialiteUser->shouldReceive('getName')->andReturn('John Doe');

            Socialite::shouldReceive('driver')
                ->with('github')
                ->once()
                ->andReturnSelf();

            Socialite::shouldReceive('user')
                ->once()
                ->andReturn($socialiteUser);

            $response = $this->get('/auth/github/callback');

            $response->assertRedirect(route('home'));

            $this->assertDatabaseHas('social_accounts', [
                'user_id' => $user->id,
                'provider_id' => '12345',
                'provider_name' => 'github',
            ]);

            $this->assertAuthenticatedAs($user);
        });

        it('redirects back on socialite exception', function () {
            Socialite::shouldReceive('driver')
                ->with('github')
                ->once()
                ->andReturnSelf();

            Socialite::shouldReceive('user')
                ->once()
                ->andThrow(new Exception('OAuth error'));

            $response = $this->get('/auth/github/callback');

            $response->assertRedirect();
        });
    });

    describe('findOrCreateUser method', function () {
        it('returns existing user for existing social account', function () {
            $user = User::factory()->create();
            $socialAccount = SocialAccount::factory()->create([
                'user_id' => $user->id,
                'provider_id' => '12345',
                'provider_name' => 'github',
            ]);

            $socialiteUser = mock(SocialiteUser::class);
            $socialiteUser->shouldReceive('getId')->andReturn('12345');

            $controller = new \App\Http\Controllers\Auth\SocialiteController;
            $result = $controller->findOrCreateUser($socialiteUser, 'github');

            expect($result->id)->toBe($user->id);
        });

        it('creates new user when no existing user or social account exists', function () {
            $socialiteUser = mock(SocialiteUser::class);
            $socialiteUser->shouldReceive('getId')->andReturn('12345');
            $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
            $socialiteUser->shouldReceive('getName')->andReturn('John Doe');

            $controller = new \App\Http\Controllers\Auth\SocialiteController;
            $result = $controller->findOrCreateUser($socialiteUser, 'github');

            expect($result->email)->toBe('john@example.com');
            expect($result->name)->toBe('John Doe');

            $this->assertDatabaseHas('social_accounts', [
                'user_id' => $result->id,
                'provider_id' => '12345',
                'provider_name' => 'github',
            ]);
        });
    });
});
