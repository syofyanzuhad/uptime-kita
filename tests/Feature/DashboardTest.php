<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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

test('dashboard shares pollRequestApi configuration to frontend', function () {
    config(['app.poll_request_api' => 'auto']);

    $response = $this->get('/dashboard');
    $response->assertInertia(fn (Assert $page) => $page
        ->where('pollRequestApi', 'auto')
    );

    config(['app.poll_request_api' => 'manual']);

    $response = $this->get('/dashboard');
    $response->assertInertia(fn (Assert $page) => $page
        ->where('pollRequestApi', 'manual')
    );
});

test('dashboard shares sseEnabled configuration to frontend', function () {
    config(['uptime-monitor.sse.enabled' => true]);

    $response = $this->get('/dashboard');
    $response->assertInertia(fn (Assert $page) => $page
        ->where('sseEnabled', true)
    );

    config(['uptime-monitor.sse.enabled' => false]);

    $response = $this->get('/dashboard');
    $response->assertInertia(fn (Assert $page) => $page
        ->where('sseEnabled', false)
    );
});
