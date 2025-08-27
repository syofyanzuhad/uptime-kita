<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('ToggleMonitorActiveController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->otherUser = User::factory()->create();
        
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        $this->disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false,
        ]);

        // User owns the private monitor
        $this->privateMonitor->users()->attach($this->user->id, ['is_owner' => true]);
    });

    it('allows admin to disable an active monitor', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertOk();
        $response->assertJson(['is_enabled' => false]);
        
        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'is_enabled' => false,
        ]);
    });

    it('allows admin to enable a disabled monitor', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->disabledMonitor->id}/toggle-active");

        $response->assertOk();
        $response->assertJson(['is_enabled' => true]);
        
        assertDatabaseHas('monitors', [
            'id' => $this->disabledMonitor->id,
            'is_enabled' => true,
        ]);
    });

    it('allows owner to toggle their private monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-active");

        $response->assertOk();
        $response->assertJson(['is_enabled' => false]);
        
        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'is_enabled' => false,
        ]);

        // Toggle back
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-active");

        $response->assertOk();
        $response->assertJson(['is_enabled' => true]);
    });

    it('prevents non-owner from toggling private monitor', function () {
        $response = actingAs($this->otherUser)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-active");

        $response->assertForbidden();
        
        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'is_enabled' => true, // Should remain unchanged
        ]);
    });

    it('prevents regular user from toggling public monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertForbidden();
        
        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'is_enabled' => true,
        ]);
    });

    it('requires authentication', function () {
        $response = postJson("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertUnauthorized();
    });

    it('handles non-existent monitor', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/999999/toggle-active");

        $response->assertNotFound();
    });

    it('toggles state correctly multiple times', function () {
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        // First toggle - disable
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$monitor->id}/toggle-active");
        $response->assertJson(['is_enabled' => false]);

        // Second toggle - enable
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$monitor->id}/toggle-active");
        $response->assertJson(['is_enabled' => true]);

        // Third toggle - disable again
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$monitor->id}/toggle-active");
        $response->assertJson(['is_enabled' => false]);
    });

    it('maintains other monitor properties when toggling', function () {
        $monitor = Monitor::factory()->create([
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'is_public' => true,
            'is_enabled' => true,
            'is_pinned' => true,
            'check_interval' => 60,
        ]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$monitor->id}/toggle-active");

        $response->assertOk();
        
        assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'is_public' => true,
            'is_enabled' => false,
            'is_pinned' => true,
            'check_interval' => 60,
        ]);
    });

    it('returns updated status in response', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-active");

        $response->assertOk();
        $response->assertJsonStructure(['is_enabled']);
        
        $isEnabled = $response->json('is_enabled');
        expect($isEnabled)->toBeFalse();
    });

    it('works with pinned monitors', function () {
        $pinnedMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'is_pinned' => true,
        ]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$pinnedMonitor->id}/toggle-active");

        $response->assertOk();
        $response->assertJson(['is_enabled' => false]);
        
        assertDatabaseHas('monitors', [
            'id' => $pinnedMonitor->id,
            'is_enabled' => false,
            'is_pinned' => true, // Pin status should remain
        ]);
    });
});