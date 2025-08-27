<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('SubscribeMonitorController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);
    });

    it('allows authenticated user to subscribe to public monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->publicMonitor->id}/subscribe");

        $response->assertOk();
        $response->assertJson(['message' => 'Subscribed to monitor successfully']);

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $this->publicMonitor->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    });

    it('prevents duplicate subscription', function () {
        // First subscription
        $this->user->monitors()->attach($this->publicMonitor->id, ['is_active' => true]);

        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->publicMonitor->id}/subscribe");

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Already subscribed to this monitor']);
    });

    it('prevents subscription to private monitor by non-owner', function () {
        $otherUser = User::factory()->create();

        $response = actingAs($otherUser)
            ->postJson("/monitor/{$this->privateMonitor->id}/subscribe");

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Cannot subscribe to private monitor']);
    });

    it('allows owner to subscribe to their private monitor', function () {
        // Make user the owner
        $this->privateMonitor->users()->attach($this->user->id, ['is_active' => true]);

        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->privateMonitor->id}/subscribe");

        $response->assertOk();

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $this->privateMonitor->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    });

    it('prevents subscription to non-existent monitor', function () {
        $response = actingAs($this->user)
            ->postJson('/monitor/999999/subscribe');

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $response = postJson("/monitor/{$this->publicMonitor->id}/subscribe");

        $response->assertUnauthorized();
    });

    it('prevents subscription to disabled monitor', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);

        $response = actingAs($this->user)
            ->postJson("/monitor/{$disabledMonitor->id}/subscribe");

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Cannot subscribe to disabled monitor']);
    });

    it('maintains owner status when subscribing', function () {
        // User is owner but not subscriber
        $this->privateMonitor->users()->attach($this->user->id, ['is_active' => true]);

        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->privateMonitor->id}/subscribe");

        $response->assertOk();

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $this->privateMonitor->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);
    });
});
