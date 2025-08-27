<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('ToggleMonitorPinController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'is_pinned' => false,
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
            'is_pinned' => false,
        ]);

        $this->pinnedMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'is_pinned' => true,
        ]);

        // User owns the private monitor
        $this->privateMonitor->users()->attach($this->user->id, ['is_owner' => true]);
    });

    it('allows admin to pin a monitor', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);
        
        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'is_pinned' => true,
        ]);
    });

    it('allows admin to unpin a monitor', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->pinnedMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => false]);
        
        assertDatabaseHas('monitors', [
            'id' => $this->pinnedMonitor->id,
            'is_pinned' => false,
        ]);
    });

    it('allows owner to toggle pin on their private monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);
        
        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'is_pinned' => true,
        ]);
    });

    it('prevents non-owner from toggling pin on private monitor', function () {
        $otherUser = User::factory()->create();

        $response = actingAs($otherUser)
            ->postJson("/monitor/{$this->privateMonitor->id}/toggle-pin");

        $response->assertForbidden();
        
        assertDatabaseHas('monitors', [
            'id' => $this->privateMonitor->id,
            'is_pinned' => false,
        ]);
    });

    it('prevents regular user from toggling pin on public monitor', function () {
        $response = actingAs($this->user)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertForbidden();
        
        assertDatabaseHas('monitors', [
            'id' => $this->publicMonitor->id,
            'is_pinned' => false,
        ]);
    });

    it('handles non-existent monitor', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/999999/toggle-pin");

        $response->assertNotFound();
    });

    it('requires authentication', function () {
        $response = postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertUnauthorized();
    });

    it('toggles pin state correctly', function () {
        // First toggle - should pin
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);

        // Second toggle - should unpin
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => false]);

        // Third toggle - should pin again
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);
    });

    it('works with disabled monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false,
            'is_pinned' => false,
        ]);

        $response = actingAs($this->admin)
            ->postJson("/monitor/{$disabledMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);
        
        assertDatabaseHas('monitors', [
            'id' => $disabledMonitor->id,
            'is_pinned' => true,
            'is_enabled' => false,
        ]);
    });

    it('returns updated pin status in response', function () {
        $response = actingAs($this->admin)
            ->postJson("/monitor/{$this->publicMonitor->id}/toggle-pin");

        $response->assertOk();
        $response->assertJsonStructure(['is_pinned']);
        
        $isPinned = $response->json('is_pinned');
        expect($isPinned)->toBeTrue();
    });
});