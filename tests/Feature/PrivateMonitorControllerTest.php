<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('PrivateMonitorController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create owned private monitors
        $this->ownedPrivateMonitor1 = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        $this->ownedPrivateMonitor2 = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        // Create subscribed private monitor
        $this->subscribedPrivateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        // Create other user's private monitor
        $this->otherPrivateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        // Create public monitor
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        // Set up relationships
        $this->ownedPrivateMonitor1->users()->attach($this->user->id, ['is_owner' => true]);
        $this->ownedPrivateMonitor2->users()->attach($this->user->id, ['is_owner' => true]);
        $this->subscribedPrivateMonitor->users()->attach($this->user->id, ['is_subscriber' => true]);
        $this->otherPrivateMonitor->users()->attach($this->otherUser->id, ['is_owner' => true]);

        // Create history for monitors
        MonitorHistory::factory()->create([
            'monitor_id' => $this->ownedPrivateMonitor1->id,
            'uptime_status' => 'up',
            'response_time' => 200,
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $this->ownedPrivateMonitor2->id,
            'uptime_status' => 'down',
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $this->subscribedPrivateMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);
    });

    it('returns private monitors for authenticated user', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'url',
                'is_public',
                'uptime_status',
                'response_time',
                'favicon_url',
            ],
        ]);
    });

    it('includes owned private monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->toContain($this->ownedPrivateMonitor1->id);
        expect($monitorIds)->toContain($this->ownedPrivateMonitor2->id);
    });

    it('includes subscribed private monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->toContain($this->subscribedPrivateMonitor->id);
    });

    it('excludes other users private monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->not->toContain($this->otherPrivateMonitor->id);
    });

    it('excludes public monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->not->toContain($this->publicMonitor->id);
    });

    it('requires authentication', function () {
        $response = get('/private-monitors');

        $response->assertRedirect('/login');
    });

    it('includes monitor status information', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $upMonitor = collect($monitors)->firstWhere('id', $this->ownedPrivateMonitor1->id);

        expect($upMonitor['uptime_status'])->toBe('up');
        expect($upMonitor['response_time'])->toBe(200);
    });

    it('excludes disabled private monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => false,
        ]);

        $disabledMonitor->users()->attach($this->user->id, ['is_owner' => true]);

        MonitorHistory::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->not->toContain($disabledMonitor->id);
    });

    it('orders monitors by created date descending', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();

        // Verify the monitors are ordered correctly
        for ($i = 0; $i < count($monitors) - 1; $i++) {
            $current = $monitors[$i];
            $next = $monitors[$i + 1];

            expect($current['id'])->toBeGreaterThan($next['id']); // Since IDs are incremental, newer has higher ID
        }
    });

    it('handles user with no private monitors', function () {
        $newUser = User::factory()->create();

        $response = actingAs($newUser)->get('/private-monitors');

        $response->assertOk();
        $response->assertJson([]);
    });

    it('returns both owned and subscribed monitors', function () {
        // User is both owner and subscriber
        $bothMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        $bothMonitor->users()->attach($this->user->id, [
            'is_owner' => true,
            'is_subscriber' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $bothMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json();
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->toContain($bothMonitor->id);
        expect($monitors)->toHaveCount(4); // 2 owned + 1 subscribed + 1 both
    });
});
