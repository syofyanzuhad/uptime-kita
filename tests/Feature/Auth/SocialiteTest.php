<?php

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('redirects to provider authentication page', function () {
    $provider = 'github';

    $providerMock = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $providerMock->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://github.com/login/oauth/authorize'));

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}");

    $response->assertRedirect();
});

test('creates new user when social account does not exist', function () {
    $provider = 'github';

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456');
    $socialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Test User');

    $providerMock = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}/callback");

    $this->assertAuthenticated();
    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $this->assertDatabaseHas('social_accounts', [
        'provider_id' => '123456',
        'provider_name' => $provider,
    ]);
});

test('logs in existing user when social account exists', function () {
    $provider = 'github';
    $user = User::factory()->create();

    $socialAccount = SocialAccount::create([
        'user_id' => $user->id,
        'provider_id' => '123456',
        'provider_name' => $provider,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456');
    $socialiteUser->shouldReceive('getEmail')->andReturn($user->email);
    $socialiteUser->shouldReceive('getName')->andReturn($user->name);

    $providerMock = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}/callback");

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home'));
});

test('links social account to existing user with same email', function () {
    $provider = 'github';
    $user = User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('789012');
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn($user->name);

    $providerMock = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}/callback");

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider_id' => '789012',
        'provider_name' => $provider,
    ]);
});

test('handles provider callback exceptions gracefully', function () {
    $provider = 'github';

    $providerMock = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $providerMock->shouldReceive('user')
        ->once()
        ->andThrow(new Exception('Provider error'));

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}/callback");

    $this->assertGuest();
    $response->assertRedirect();
});

test('supports multiple providers', function ($provider) {
    $providerMock = Mockery::mock("Laravel\Socialite\Two\\".ucfirst($provider).'Provider');
    $providerMock->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect("https://{$provider}.com/oauth"));

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}");

    $response->assertRedirect();
})->with([
    'github',
    'google',
    'facebook',
    'twitter',
]);

test('same social account cannot be linked to multiple users', function () {
    $provider = 'github';
    $providerId = '123456';

    $user1 = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user1->id,
        'provider_id' => $providerId,
        'provider_name' => $provider,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn($providerId);
    $socialiteUser->shouldReceive('getEmail')->andReturn('different@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Different User');

    $providerMock = Mockery::mock('Laravel\Socialite\Two\GithubProvider');
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}/callback");

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user1);
    $response->assertRedirect(route('home'));

    expect(User::where('email', 'different@example.com')->exists())->toBeFalse();
});

test('creates user without email if provider does not provide email', function () {
    $provider = 'twitter';

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('twitter123');
    $socialiteUser->shouldReceive('getEmail')->andReturn(null);
    $socialiteUser->shouldReceive('getName')->andReturn('Twitter User');

    $providerMock = Mockery::mock('Laravel\Socialite\Two\TwitterProvider');
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with($provider)
        ->once()
        ->andReturn($providerMock);

    $response = $this->get("/auth/{$provider}/callback");

    $this->assertAuthenticated();
    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('users', [
        'name' => 'Twitter User',
        'email' => null,
    ]);

    $this->assertDatabaseHas('social_accounts', [
        'provider_id' => 'twitter123',
        'provider_name' => $provider,
    ]);
});
