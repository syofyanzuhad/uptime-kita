<?php

use App\Models\TelemetryPing;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('admin can view telemetry dashboard when receiver disabled', function () {
    config(['telemetry.receiver_enabled' => false]);

    $response = $this->actingAs($this->admin)->get('/admin/telemetry');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/TelemetryDashboard')
        ->where('receiverEnabled', false)
        ->has('recentPings')
    );
});

test('admin can view telemetry dashboard with data', function () {
    config(['telemetry.receiver_enabled' => true]);

    TelemetryPing::create([
        'instance_id' => 'abc-12345678',
        'app_version' => '1.0.0',
        'php_version' => '8.4',
        'laravel_version' => '13',
        'monitors_total' => 3,
        'users_total' => 2,
        'os_type' => 'Linux',
        'first_seen_at' => now(),
        'last_ping_at' => now(),
        'ping_count' => 1,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/telemetry');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/TelemetryDashboard')
        ->where('receiverEnabled', true)
        ->has('statistics')
        ->has('versionDistribution')
        ->has('osDistribution')
        ->has('growthData')
        ->has('recentPings', 1)
    );
});

test('non-admin cannot view telemetry dashboard', function () {
    $response = $this->actingAs($this->user)->get('/admin/telemetry');

    $response->assertForbidden();
});

test('admin can fetch telemetry stats', function () {
    config(['telemetry.receiver_enabled' => true]);

    $response = $this->actingAs($this->admin)->getJson('/admin/telemetry/stats');

    $response->assertOk();
    $response->assertJsonStructure(['statistics', 'versionDistribution', 'osDistribution']);
});

test('stats returns error when receiver disabled', function () {
    config(['telemetry.receiver_enabled' => false]);

    $response = $this->actingAs($this->admin)->getJson('/admin/telemetry/stats');

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Receiver not enabled']);
});

test('non-admin cannot fetch telemetry stats', function () {
    $response = $this->actingAs($this->user)->getJson('/admin/telemetry/stats');

    $response->assertForbidden();
});
