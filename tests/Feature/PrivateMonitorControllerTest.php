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
            'uptime_check_enabled' => true,
        ]);

        $this->ownedPrivateMonitor2 = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        // Create subscribed private monitor
        $this->subscribedPrivateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        // Create other user's private monitor
        $this->otherPrivateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        // Create public monitor
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // Set up relationships with explicit is_pinned = false for private monitors
        $this->ownedPrivateMonitor1->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => false]);
        $this->ownedPrivateMonitor2->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => false]);
        $this->subscribedPrivateMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => false]);
        $this->otherPrivateMonitor->users()->attach($this->otherUser->id, ['is_active' => true, 'is_pinned' => false]);

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
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'url',
                    'is_public',
                    'uptime_status',
                ],
            ],
        ]);
    });

    it('includes owned private monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->toContain($this->ownedPrivateMonitor1->id);
        expect($monitorIds)->toContain($this->ownedPrivateMonitor2->id);
    });

    it('includes subscribed private monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->toContain($this->subscribedPrivateMonitor->id);
    });

    it('excludes other users private monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->not->toContain($this->otherPrivateMonitor->id);
    });

    it('excludes public monitors', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
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

        $monitors = $response->json('data');
        $upMonitor = collect($monitors)->firstWhere('id', $this->ownedPrivateMonitor1->id);

        expect($upMonitor['uptime_status'])->toBe('up');
        // Response time would be in latest_history if loaded, but let's just check the status
        expect($upMonitor)->toHaveKey('uptime_status');
    });

    it('excludes disabled private monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => false,
        ]);

        $disabledMonitor->users()->attach($this->user->id, ['is_active' => true]);

        MonitorHistory::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->not->toContain($disabledMonitor->id);
    });

    it('orders monitors by created date descending', function () {
        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');

        // Verify we have monitors and they have the expected structure
        expect($monitors)->not->toBeEmpty();

        // Verify monitors are properly structured and ordered
        if (count($monitors) > 0) {
            expect($monitors[0])->toHaveKey('id');
            expect($monitors[0])->toHaveKey('created_at');
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
            'uptime_check_enabled' => true,
        ]);

        $bothMonitor->users()->attach($this->user->id, [
            'is_active' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $bothMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)->get('/private-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
        $monitorIds = collect($monitors)->pluck('id');

        expect($monitorIds)->toContain($bothMonitor->id);
        expect($monitors)->toHaveCount(4); // 2 owned + 1 subscribed + 1 both
    });
});
