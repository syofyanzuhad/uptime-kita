<?php

use App\Models\TelemetryPing;
use App\Models\User;
use App\Services\InstanceIdService;
use App\Services\TelemetryService;

beforeEach(function () {
    // Clean up instance ID file before each test
    $path = config('telemetry.instance_id_path');
    if (file_exists($path)) {
        unlink($path);
    }
});

// === Settings Page Tests ===

test('telemetry settings page requires authentication', function () {
    $response = $this->get('/settings/telemetry');

    $response->assertRedirect('/login');
});

test('non-admin user cannot access telemetry settings page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/settings/telemetry');

    $response->assertForbidden();
});

test('admin user can access telemetry settings page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/settings/telemetry');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Telemetry')
        ->has('settings')
        ->has('previewData')
    );
});

// === Instance ID Service Tests ===

test('instance id service generates valid SHA-256 hash', function () {
    $service = new InstanceIdService;
    $id = $service->getInstanceId();

    expect($id)->toHaveLength(64);
    expect(ctype_xdigit($id))->toBeTrue();
});

test('instance id service returns same ID on subsequent calls', function () {
    $service = new InstanceIdService;
    $id1 = $service->getInstanceId();
    $id2 = $service->getInstanceId();

    expect($id1)->toBe($id2);
});

test('instance id service can regenerate ID', function () {
    $service = new InstanceIdService;
    $id1 = $service->getInstanceId();
    $id2 = $service->regenerateInstanceId();

    expect($id1)->not->toBe($id2);
    expect($id2)->toHaveLength(64);
});

test('instance id service returns install date', function () {
    $service = new InstanceIdService;
    $service->getInstanceId(); // Ensure ID exists
    $date = $service->getInstallDate();

    expect($date)->toBe(date('Y-m-d'));
});

// === Telemetry Service Tests ===

test('telemetry service collects valid data', function () {
    $service = app(TelemetryService::class);
    $data = $service->collectData();

    expect($data)->toHaveKeys([
        'instance_id',
        'versions',
        'stats',
        'system',
        'ping',
    ]);

    expect($data['instance_id'])->toHaveLength(64);
    expect($data['versions'])->toHaveKeys(['app', 'php', 'laravel']);
    expect($data['stats'])->toHaveKeys(['monitors_total', 'monitors_public', 'users_total', 'status_pages_total', 'install_date']);
    expect($data['system'])->toHaveKeys(['os_family', 'os_type', 'database_driver', 'queue_driver', 'cache_driver']);
    expect($data['ping'])->toHaveKeys(['timestamp', 'timezone']);
});

test('telemetry service respects enabled config', function () {
    config(['telemetry.enabled' => false]);
    $service = app(TelemetryService::class);

    expect($service->isEnabled())->toBeFalse();

    config(['telemetry.enabled' => true]);
    expect($service->isEnabled())->toBeTrue();
});

// === Telemetry Receiver Tests ===

test('telemetry receiver returns 403 when disabled', function () {
    config(['telemetry.receiver_enabled' => false]);

    $response = $this->postJson('/api/telemetry/ping', [
        'instance_id' => str_repeat('a', 64),
        'versions' => ['app' => '1.0', 'php' => '8.3', 'laravel' => '12.0'],
        'stats' => ['monitors_total' => 5],
        'system' => ['os_family' => 'Linux'],
        'ping' => ['timestamp' => now()->toIso8601String()],
    ]);

    $response->assertForbidden();
});

test('telemetry receiver validates incoming data', function () {
    config(['telemetry.receiver_enabled' => true]);

    $response = $this->postJson('/api/telemetry/ping', [
        'instance_id' => 'invalid', // Too short
    ]);

    $response->assertStatus(422);
});

test('telemetry receiver accepts valid ping', function () {
    config(['telemetry.receiver_enabled' => true]);

    $instanceId = str_repeat('a', 64);

    $response = $this->postJson('/api/telemetry/ping', [
        'instance_id' => $instanceId,
        'versions' => ['app' => '2025-07-15', 'php' => '8.3.0', 'laravel' => '12.0.0'],
        'stats' => [
            'monitors_total' => 10,
            'monitors_public' => 5,
            'users_total' => 3,
            'status_pages_total' => 2,
            'install_date' => '2025-01-01',
        ],
        'system' => [
            'os_family' => 'Linux',
            'os_type' => 'Ubuntu',
            'database_driver' => 'sqlite',
            'queue_driver' => 'database',
            'cache_driver' => 'database',
        ],
        'ping' => [
            'timestamp' => now()->toIso8601String(),
            'timezone' => 'UTC',
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    // Verify data was stored
    $ping = TelemetryPing::where('instance_id', $instanceId)->first();
    expect($ping)->not->toBeNull();
    expect($ping->monitors_total)->toBe(10);
    expect($ping->php_version)->toBe('8.3.0');
    expect($ping->os_type)->toBe('Ubuntu');
});

test('telemetry receiver updates existing instance on subsequent pings', function () {
    config(['telemetry.receiver_enabled' => true]);

    $instanceId = str_repeat('b', 64);

    // First ping
    $this->postJson('/api/telemetry/ping', [
        'instance_id' => $instanceId,
        'versions' => ['app' => '1.0', 'php' => '8.2', 'laravel' => '11.0'],
        'stats' => ['monitors_total' => 5],
        'system' => ['os_family' => 'Linux'],
        'ping' => ['timestamp' => now()->toIso8601String()],
    ]);

    // Second ping with updated data
    $this->postJson('/api/telemetry/ping', [
        'instance_id' => $instanceId,
        'versions' => ['app' => '2.0', 'php' => '8.3', 'laravel' => '12.0'],
        'stats' => ['monitors_total' => 10],
        'system' => ['os_family' => 'Linux'],
        'ping' => ['timestamp' => now()->toIso8601String()],
    ]);

    // Should only have one record
    expect(TelemetryPing::where('instance_id', $instanceId)->count())->toBe(1);

    // Data should be updated
    $ping = TelemetryPing::where('instance_id', $instanceId)->first();
    expect($ping->monitors_total)->toBe(10);
    expect($ping->php_version)->toBe('8.3');
    expect($ping->ping_count)->toBe(2);
});

// === Dashboard Tests ===

test('telemetry dashboard requires authentication', function () {
    $response = $this->get('/admin/telemetry');

    $response->assertRedirect('/login');
});

test('non-admin user cannot access telemetry dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/admin/telemetry');

    $response->assertForbidden();
});

test('admin user can access telemetry dashboard', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($user)->get('/admin/telemetry');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/TelemetryDashboard')
        ->has('receiverEnabled')
    );
});

// === Regenerate Instance ID Tests ===

test('non-admin cannot regenerate instance ID', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->postJson('/settings/telemetry/regenerate-id');

    $response->assertForbidden();
});

test('admin can regenerate instance ID', function () {
    $user = User::factory()->create(['is_admin' => true]);

    // Create initial ID
    $service = new InstanceIdService;
    $oldId = $service->getInstanceId();

    $response = $this->actingAs($user)->postJson('/settings/telemetry/regenerate-id');

    $response->assertOk();
    $response->assertJson(['success' => true]);

    // Verify ID changed
    $newId = $service->getInstanceId();
    expect($newId)->not->toBe($oldId);
});
