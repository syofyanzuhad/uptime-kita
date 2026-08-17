<?php

use App\Jobs\SendTelemetryPingJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('admin can view telemetry settings page', function () {
    $response = $this->actingAs($this->admin)->get('/settings/telemetry');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Telemetry')
        ->has('settings')
        ->has('previewData')
    );
});

test('non-admin cannot view telemetry settings page', function () {
    $response = $this->actingAs($this->user)->get('/settings/telemetry');

    $response->assertForbidden();
});

test('admin can preview telemetry data', function () {
    $response = $this->actingAs($this->admin)->getJson('/settings/telemetry/preview');

    $response->assertOk();
    $response->assertJsonStructure(['instance_id']);
});

test('non-admin cannot preview telemetry data', function () {
    $response = $this->actingAs($this->user)->getJson('/settings/telemetry/preview');

    $response->assertForbidden();
});

test('admin can send a test ping when telemetry enabled', function () {
    Queue::fake();
    config(['telemetry.enabled' => true]);

    $response = $this->actingAs($this->admin)->postJson('/settings/telemetry/test-ping');

    $response->assertOk();
    $response->assertJson(['success' => true]);
    Queue::assertPushed(SendTelemetryPingJob::class);
});

test('test ping fails when telemetry disabled', function () {
    Queue::fake();
    config(['telemetry.enabled' => false]);

    $response = $this->actingAs($this->admin)->postJson('/settings/telemetry/test-ping');

    $response->assertStatus(400);
    Queue::assertNotPushed(SendTelemetryPingJob::class);
});

test('non-admin cannot send a test ping', function () {
    $response = $this->actingAs($this->user)->postJson('/settings/telemetry/test-ping');

    $response->assertForbidden();
});

test('admin can regenerate instance id', function () {
    $response = $this->actingAs($this->admin)->postJson('/settings/telemetry/regenerate-id');

    $response->assertOk();
    $response->assertJson(['success' => true]);
    $response->assertJsonStructure(['instance_id']);
});

test('non-admin cannot regenerate instance id', function () {
    $response = $this->actingAs($this->user)->postJson('/settings/telemetry/regenerate-id');

    $response->assertForbidden();
});
