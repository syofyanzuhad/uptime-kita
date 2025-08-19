<?php

use App\Models\Monitor;
use App\Models\User;

it('can pin a monitor', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'is_public' => false,
        'uptime_check_enabled' => true,
    ]);

    // Attach user to monitor
    $monitor->users()->attach($user->id, [
        'is_active' => true,
        'is_pinned' => false,
    ]);

    $this->actingAs($user)
        ->postJson("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'is_pinned' => true,
        ]);

    $this->assertDatabaseHas('user_monitor', [
        'user_id' => $user->id,
        'monitor_id' => $monitor->id,
        'is_pinned' => true,
    ]);
});

it('can unpin a monitor', function () {
    // Clear any cached data from previous tests
    cache()->flush();

    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'is_public' => false,
    ]);

    // Attach user to monitor and pin it
    $monitor->users()->attach($user->id, [
        'is_active' => true,
        'is_pinned' => true,
    ]);

    $this->actingAs($user)
        ->postJson("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => false,
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'is_pinned' => false,
        ]);

    $this->assertDatabaseHas('user_monitor', [
        'user_id' => $user->id,
        'monitor_id' => $monitor->id,
        'is_pinned' => 0,
    ]);
});

it('cannot pin a monitor if not subscribed', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'is_public' => false,
    ]);

    $this->actingAs($user)
        ->postJson("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ])
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'You must be subscribed to this monitor to pin it.',
        ]);
});

it('requires authentication to pin a monitor', function () {
    $monitor = Monitor::factory()->create();

    $this->postJson("/monitor/{$monitor->id}/toggle-pin", [
        'is_pinned' => true,
    ])
        ->assertStatus(401);
});

it('requires is_pinned parameter', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create();

    $monitor->users()->attach($user->id, [
        'is_active' => true,
        'is_pinned' => false,
    ]);

    $this->actingAs($user)
        ->postJson("/monitor/{$monitor->id}/toggle-pin", [])
        ->assertStatus(422);
});
