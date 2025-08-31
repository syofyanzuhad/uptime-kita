<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with non existing email', function () {
    $this->post('/login', [
        'email' => 'non-existing@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with empty credentials', function () {
    $response = $this->post('/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});

test('users can not authenticate with invalid email format', function () {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('authenticated users are redirected when accessing login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('unauthenticated users cannot logout', function () {
    $response = $this->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});

test('remember me functionality works', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertCookie(\Illuminate\Support\Facades\Auth::guard()->getRecallerName());
});

test('user can login without remember me', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $response->assertCookieMissing(\Illuminate\Support\Facades\Auth::guard()->getRecallerName());
});

test('login attempts are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::clear('login:'.request()->ip());

    for ($i = 0; $i < 6; $i++) {
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response->assertSessionHasErrors('email');
    $errorMessage = strtolower($response->getSession()->get('errors')->first('email'));
    expect($errorMessage)->toContain('too many');

    RateLimiter::clear('login:'.request()->ip());
});
