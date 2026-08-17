<?php

use App\Models\User;

test('appearance settings page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/appearance');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('settings/Appearance'));
});

test('appearance settings page requires authentication', function () {
    $response = $this->get('/settings/appearance');

    $response->assertRedirect('/login');
});
