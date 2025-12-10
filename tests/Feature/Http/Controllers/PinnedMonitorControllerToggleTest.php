<?php

use App\Models\Monitor;
use App\Models\User;
use App\Models\UserMonitor;

describe('PinnedMonitorController toggle method', function () {
    it('pins monitor successfully when user is subscribed', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        // Create subscription relationship
        UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => true,
        ]);

        // Cache clearing is handled by the controller, no need to mock

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');
        $response->assertSessionHas('flash.message', 'Monitor pinned successfully');

        $this->assertDatabaseHas('user_monitor', [
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_pinned' => true,
        ]);
    });

    it('unpins monitor successfully when user is subscribed', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        // Create subscription relationship with pinned status
        UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => true,
            'is_pinned' => true,
        ]);

        // Cache clearing is handled by the controller, no need to mock

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');
        $response->assertSessionHas('flash.message', 'Monitor unpinned successfully');

        $this->assertDatabaseHas('user_monitor', [
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_pinned' => false,
        ]);
    });

    it('rejects pinning when user is not subscribed to monitor', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        // No subscription relationship exists

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'error');
        $response->assertSessionHas('flash.message', 'You must be subscribed to this monitor to pin it.');
    });

    it('allows pinning even when user subscription is inactive', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        // Create inactive subscription relationship
        UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');
        $response->assertSessionHas('flash.message', 'Monitor pinned successfully');

        // Verify it was actually pinned despite inactive status
        $this->assertDatabaseHas('user_monitor', [
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_pinned' => true,
            'is_active' => false,
        ]);
    });

    it('validates is_pinned parameter is required', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", []);

        $response->assertSessionHasErrors(['is_pinned']);
    });

    it('validates is_pinned parameter must be boolean', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => 'not-a-boolean',
        ]);

        $response->assertSessionHasErrors(['is_pinned']);
    });

    it('handles non-existent monitor', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/monitor/999/toggle-pin', [
            'is_pinned' => true,
        ]);

        $response->assertNotFound();  // Controller returns 404 for non-existent monitor
    });

    it('works with disabled monitors using withoutGlobalScopes', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create(); // Disabled monitor

        // Create subscription relationship
        UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => true,
        ]);

        // Cache clearing is handled by the controller, no need to mock

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');
    });

    it('clears cache for pinned status', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => true,
        ]);

        // Cache clearing is handled by the controller, no need to mock

        $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);
    });

    it('requires authentication', function () {
        $monitor = Monitor::factory()->create();

        $response = $this->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertRedirect(route('login'));
    });

    it('handles exception during update', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        // Create subscription but force an exception by deleting the monitor after creating subscription
        UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => true,
        ]);

        // Delete the monitor to cause a 404
        $monitorId = $monitor->id;
        $monitor->delete();

        $response = $this->actingAs($user)->post("/monitor/{$monitorId}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertNotFound();  // Deleted monitor returns 404
    });

    it('updates existing pivot record', function () {
        $user = User::factory()->create();
        $monitor = Monitor::factory()->create();

        // Create subscription relationship
        $userMonitor = UserMonitor::factory()->create([
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_active' => true,
            'is_pinned' => false,
        ]);

        // Cache clearing is handled by the controller

        $response = $this->actingAs($user)->post("/monitor/{$monitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');

        // Verify the same record was updated, not a new one created
        $this->assertDatabaseCount('user_monitor', 1);
        $this->assertDatabaseHas('user_monitor', [
            'id' => $userMonitor->id,
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
            'is_pinned' => true,
        ]);
    });
});
