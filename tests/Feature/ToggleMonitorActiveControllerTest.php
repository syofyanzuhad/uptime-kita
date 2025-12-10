<?php

use App\Models\Monitor;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

describe('ToggleMonitorActiveController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->otherUser = User::factory()->create();

        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        $this->disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);

        // User owns the private monitor
        $this->privateMonitor->users()->attach($this->user->id, ['is_active' => true]);
    });

    it('allows admin to disable an active monitor', function () {
        // Admin needs to be attached to the monitor to toggle it
        $this->publicMonitor->users()->attach($this->admin->id, ['is_active' => true]);

        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'uptime_check_enabled' => false,
        ]);
    });

    it('allows admin to enable a disabled monitor', function () {
        // Admin needs to be attached to the monitor
        $this->disabledMonitor->users()->attach($this->admin->id, ['is_active' => true]);

        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$this->disabledMonitor->id}/toggle-active");

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('monitors', [
            'id' => $this->disabledMonitor->id,
            'uptime_check_enabled' => true,
        ]);
    });

    it('allows owner to toggle their private monitor', function () {
        $response = actingAs($this->user)
            ->from('/dashboard')
            ->post("/monitor/{$this->privateMonitor->id}/toggle-active");

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'uptime_check_enabled' => false,
        ]);

        // Toggle back
        $response = actingAs($this->user)
            ->from('/dashboard')
            ->post("/monitor/{$this->privateMonitor->id}/toggle-active");

        $response->assertRedirect('/dashboard');
        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'uptime_check_enabled' => true,
        ]);
    });

    it('prevents non-owner from toggling private monitor', function () {
        $response = actingAs($this->otherUser)
            ->from('/dashboard')
            ->post("/monitor/{$this->privateMonitor->id}/toggle-active");

        // User not subscribed will get redirect with error
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('flash.type', 'error');

        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'uptime_check_enabled' => true, // Should remain unchanged
        ]);
    });

    it('prevents regular user from toggling public monitor not subscribed to', function () {
        $response = actingAs($this->user)
            ->from('/dashboard')
            ->post("/monitor/{$this->publicMonitor->id}/toggle-active");

        // User not subscribed will get redirect with error
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('flash.type', 'error');

        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'uptime_check_enabled' => true,
        ]);
    });

    it('requires authentication', function () {
        $response = post("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertRedirect('/login');
    });

    it('handles non-existent monitor', function () {
        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post('/monitor/999999/toggle-active');

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('flash.type', 'error');
    });

    it('toggles state correctly multiple times', function () {
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor->users()->attach($this->admin->id, ['is_active' => true]);

        // First toggle - disable
        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$monitor->id}/toggle-active");
        $response->assertRedirect();
        assertDatabaseHas('monitors', ['id' => $monitor->id, 'uptime_check_enabled' => false]);

        // Second toggle - enable
        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$monitor->id}/toggle-active");
        $response->assertRedirect();
        assertDatabaseHas('monitors', ['id' => $monitor->id, 'uptime_check_enabled' => true]);

        // Third toggle - disable again
        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$monitor->id}/toggle-active");
        $response->assertRedirect();
        assertDatabaseHas('monitors', ['id' => $monitor->id, 'uptime_check_enabled' => false]);
    });

    it('maintains other monitor properties when toggling', function () {
        $monitor = Monitor::factory()->create([
            'url' => 'https://example-toggle.com',
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor->users()->attach($this->admin->id, ['is_active' => true]);

        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$monitor->id}/toggle-active");

        $response->assertRedirect();

        assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'url' => 'https://example-toggle.com',
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);
    });

    it('returns success message in session', function () {
        $this->publicMonitor->users()->attach($this->admin->id, ['is_active' => true]);

        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'uptime_check_enabled' => false,
        ]);
    });

    it('works with pinned monitors', function () {
        $pinnedMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        // Set as pinned through pivot table
        $pinnedMonitor->users()->attach($this->admin->id, ['is_active' => true, 'is_pinned' => true]);

        $response = actingAs($this->admin)
            ->from('/dashboard')
            ->post("/monitor/{$pinnedMonitor->id}/toggle-active");

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('monitors', [
            'id' => $pinnedMonitor->id,
            'uptime_check_enabled' => false,
        ]);

        // Check pin status remains in pivot table
        assertDatabaseHas('user_monitor', [
            'monitor_id' => $pinnedMonitor->id,
            'user_id' => $this->admin->id,
            'is_pinned' => true,
        ]);
    });
});
