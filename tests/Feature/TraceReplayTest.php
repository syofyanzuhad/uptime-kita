<?php

use App\Models\User;

test('trace-replay dashboard requires authentication', function () {
    $response = $this->get('/trace-replay');

    $response->assertRedirect('/login');
});

test('non-admin user cannot access trace-replay dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/trace-replay');

    $response->assertForbidden();
});

test('admin user can access trace-replay dashboard', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/trace-replay');

    $response->assertOk();
});
