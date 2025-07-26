<?php

use App\Models\User;

test('guests are not redirected to the login page', function () {
    $response = $this->get('/dashboard');

    // Check that the response does not redirect to the login page
    $response->assertStatus(200);
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});
