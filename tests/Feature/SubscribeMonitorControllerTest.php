<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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

    describe('non-JSON requests (redirect responses)', function () {
        it('returns redirect response for successful public monitor subscription', function () {
            $response = actingAs($this->user)
                ->post("/monitor/{$this->publicMonitor->id}/subscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
            $response->assertSessionHas('flash.message', 'Berhasil berlangganan monitor: '.$this->publicMonitor->url);

            assertDatabaseHas('user_monitor', [
                'monitor_id' => $this->publicMonitor->id,
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);
        });

        it('returns redirect response for private monitor error', function () {
            $otherUser = User::factory()->create();

            $response = actingAs($otherUser)
                ->post("/monitor/{$this->privateMonitor->id}/subscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Cannot subscribe to private monitor');
        });

        it('returns redirect response for disabled monitor error', function () {
            $disabledMonitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => false,
            ]);

            $response = actingAs($this->user)
                ->post("/monitor/{$disabledMonitor->id}/subscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Cannot subscribe to disabled monitor');
        });

        it('returns redirect response for duplicate subscription error', function () {
            // First subscription
            $this->user->monitors()->attach($this->publicMonitor->id, ['is_active' => true]);

            $response = actingAs($this->user)
                ->post("/monitor/{$this->publicMonitor->id}/subscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Already subscribed to this monitor');
        });

        it('returns redirect success for private monitor idempotent subscription', function () {
            // User is already subscribed to private monitor
            $this->privateMonitor->users()->attach($this->user->id, ['is_active' => true]);

            $response = actingAs($this->user)
                ->post("/monitor/{$this->privateMonitor->id}/subscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
            $response->assertSessionHas('flash.message', 'Berhasil berlangganan monitor: '.$this->privateMonitor->url);
        });

        it('returns redirect error for monitor not found', function () {
            $response = actingAs($this->user)
                ->post('/monitor/999999/subscribe');

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Monitor tidak ditemukan');
        });
    });

    describe('cache management', function () {
        it('clears user cache after successful subscription', function () {
            // Set cache to verify it gets cleared
            Cache::put('public_monitors_authenticated_'.$this->user->id, 'cached_data');

            $response = actingAs($this->user)
                ->postJson("/monitor/{$this->publicMonitor->id}/subscribe");

            $response->assertOk();

            // Verify cache is cleared
            expect(Cache::get('public_monitors_authenticated_'.$this->user->id))->toBeNull();
        });
    });

    describe('exception handling', function () {
        it('handles database integrity violations gracefully', function () {
            // Test that the controller handles unique constraint violations
            // when trying to create duplicate user-monitor relationships
            $this->user->monitors()->attach($this->publicMonitor->id, ['is_active' => true]);

            // Try to subscribe again - should be caught by the controller logic
            $response = actingAs($this->user)
                ->postJson("/monitor/{$this->publicMonitor->id}/subscribe");

            $response->assertStatus(400);
            $response->assertJson(['message' => 'Already subscribed to this monitor']);
        });
    });

    describe('edge cases', function () {
        it('handles monitors with null URL gracefully', function () {
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
                'url' => '',
            ]);

            $response = actingAs($this->user)
                ->post("/monitor/{$monitor->id}/subscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');

            // Should handle empty URL gracefully in message
            $flashMessage = session('flash.message');
            expect($flashMessage)->toContain('Berhasil berlangganan monitor:');
        });

        it('works with monitors using withoutGlobalScopes', function () {
            $monitor = Monitor::withoutGlobalScopes()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
                'url' => 'https://scoped-example.com',
                'uptime_status' => 'up',
                'uptime_last_check_date' => now(),
                'uptime_status_last_change_date' => now(),
                'certificate_status' => 'not applicable',
            ]);

            $response = actingAs($this->user)
                ->postJson("/monitor/{$monitor->id}/subscribe");

            $response->assertOk();
            $response->assertJson(['message' => 'Subscribed to monitor successfully']);
        });
    });
});
