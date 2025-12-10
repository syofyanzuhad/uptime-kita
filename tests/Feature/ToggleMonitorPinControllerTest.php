<?php

use App\Models\Monitor;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

describe('ToggleMonitorPinController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        $this->pinnedMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // User owns the private monitor
        $this->privateMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => false]);
    });

    it('allows admin to pin a monitor', function () {
        // Admin must be subscribed to the monitor
        $this->publicMonitor->users()->attach($this->admin->id, ['is_active' => true, 'is_pinned' => false]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $this->publicMonitor->id,
            'user_id' => $this->admin->id,
            'is_pinned' => true,
        ]);
    });

    it('allows admin to unpin a monitor', function () {
        // Admin must be subscribed with pinned status
        $this->pinnedMonitor->users()->attach($this->admin->id, ['is_active' => true, 'is_pinned' => true]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->pinnedMonitor->id}/toggle-pin", [
                'is_pinned' => false,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => false]);

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $this->pinnedMonitor->id,
            'user_id' => $this->admin->id,
            'is_pinned' => false,
        ]);
    });

    it('allows owner to toggle pin on their private monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $this->privateMonitor->id,
            'user_id' => $this->user->id,
            'is_pinned' => true,
        ]);
    });

    it('prevents non-owner from toggling pin on private monitor', function () {
        $otherUser = User::factory()->create();

        $response = actingAs($otherUser)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertForbidden();

        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
        ]);
    });

    it('prevents regular user from toggling pin on public monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertForbidden();

        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
        ]);
    });

    it('handles non-existent monitor', function () {
        $response = actingAs($this->admin)
            ->postJson('/monitor/999999/toggle-pin', [
                'is_pinned' => true,
            ]);

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $response = postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
            'is_pinned' => true,
        ]);

        $response->assertUnauthorized();
    });

    it('toggles pin state correctly', function () {
        // Admin must be subscribed to the monitor
        $this->publicMonitor->users()->attach($this->admin->id, ['is_active' => true, 'is_pinned' => false]);

        // First toggle - should pin
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);

        // Second toggle - should unpin
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
                'is_pinned' => false,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => false]);

        // Third toggle - should pin again
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);
    });

    it('works with disabled monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);

        // Admin must be subscribed to the monitor
        $disabledMonitor->users()->attach($this->admin->id, ['is_active' => true, 'is_pinned' => false]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$disabledMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);

        assertDatabaseHas('user_monitor', [
            'monitor_id' => $disabledMonitor->id,
            'user_id' => $this->admin->id,
            'is_pinned' => true,
        ]);
    });

    it('returns updated pin status in response', function () {
        // Admin must be subscribed to the monitor
        $this->publicMonitor->users()->attach($this->admin->id, ['is_active' => true, 'is_pinned' => false]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin", [
                'is_pinned' => true,
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['is_pinned']);

        $isPinned = $response->json('is_pinned');
        expect($isPinned)->toBeTrue();
    });
});
