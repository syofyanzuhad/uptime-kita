<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('auth.password_confirmed_at');
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    $response->assertSessionMissing('auth.password_confirmed_at');
});

test('password cannot be confirmed with empty password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => '',
    ]);

    $response->assertSessionHasErrors('password');
    $response->assertSessionMissing('auth.password_confirmed_at');
});

test('password cannot be confirmed without password field', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', []);

    $response->assertSessionHasErrors('password');
    $response->assertSessionMissing('auth.password_confirmed_at');
});

test('unauthenticated users cannot access confirm password screen', function () {
    $response = $this->get('/confirm-password');

    $response->assertRedirect('/login');
});

test('unauthenticated users cannot confirm password', function () {
    $response = $this->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect('/login');
});

test('password confirmation timeout works correctly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertSessionHas('auth.password_confirmed_at');

    $this->travel(config('auth.password_timeout', 10800) + 1)->seconds();

    $response = $this->actingAs($user)->get(route('password.confirm'));
    $response->assertStatus(200);
});

test('user is redirected to intended url after confirming password', function () {
    $user = User::factory()->create();

    session()->put('url.intended', '/profile');

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect('/profile');
});

test('user is redirected to default route when no intended url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
});
