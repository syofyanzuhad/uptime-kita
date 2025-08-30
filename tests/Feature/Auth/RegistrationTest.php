<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});

test('user cannot register with existing email', function () {
    $user = User::factory()->create();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('user cannot register with invalid email', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('user cannot register without name', function () {
    $response = $this->post('/register', [
        'name' => '',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});

test('user cannot register with short password', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'pass',
        'password_confirmation' => 'pass',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('user cannot register with unconfirmed password', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('user cannot register without password confirmation', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('user cannot register with empty form', function () {
    $response = $this->post('/register', []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

test('authenticated users are redirected when accessing registration page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/register');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('password is properly hashed on registration', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'hashed@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'hashed@example.com')->first();

    expect($user)->not->toBeNull();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('user can register with long name', function () {
    $longName = str_repeat('a', 255);

    $response = $this->post('/register', [
        'name' => $longName,
        'email' => 'longname@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'longname@example.com',
    ]);
});

test('user cannot register with name longer than 255 characters', function () {
    $tooLongName = str_repeat('a', 256);

    $response = $this->post('/register', [
        'name' => $tooLongName,
        'email' => 'toolongname@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['name']);
    $this->assertGuest();
});
