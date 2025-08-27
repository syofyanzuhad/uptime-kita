<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('PinnedMonitorController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        // Create monitors
        $this->pinnedPublicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $this->pinnedPrivateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        // Create non-pinned monitors
        $this->unpinnedMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // Set up pinned relationships through pivot table
        $this->pinnedPublicMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => true]);
        $this->pinnedPrivateMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => true]);
        $this->unpinnedMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => false]);

        // Create history for monitors
        MonitorHistory::factory()->create([
            'monitor_id' => $this->pinnedPublicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 200,
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $this->pinnedPrivateMonitor->id,
            'uptime_status' => 'down',
            'response_time' => null,
            'created_at' => now(),
        ]);
    });

    it('returns pinned monitors for authenticated user', function () {
        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $data = $response->json('data');
        expect($data)->toHaveCount(2); // Both public and owned private pinned monitors
    });

    it('includes public pinned monitors', function () {
        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->pinnedPublicMonitor->id,
            'is_pinned' => true,
        ]);
    });

    it('includes owned private pinned monitors', function () {
        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $this->pinnedPrivateMonitor->id,
            'is_pinned' => true,
        ]);
    });

    it('excludes non-owned private pinned monitors', function () {
        $otherUser = User::factory()->create();
        $privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);
        $privateMonitor->users()->attach($otherUser->id, ['is_active' => true, 'is_pinned' => true]);

        MonitorHistory::factory()->create([
            'monitor_id' => $privateMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $response->assertJsonMissing([
            'id' => $privateMonitor->id,
        ]);
    });

    it('excludes unpinned monitors', function () {
        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $response->assertJsonMissing([
            'id' => $this->unpinnedMonitor->id,
        ]);
    });

    it('includes monitor status and metrics', function () {
        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();

        $monitors = $response->json('data');

        expect($monitors[0])->toHaveKeys([
            'id',
            'name',
            'url',
            'is_pinned',
            'uptime_status',
        ]);
    });

    it('requires authentication to view pinned monitors', function () {
        $response = get('/pinned-monitors');

        $response->assertRedirect('/login');
    });

    it('excludes disabled pinned monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);
        $disabledMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => true]);

        MonitorHistory::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $response->assertJsonMissing([
            'id' => $disabledMonitor->id,
        ]);
    });

    it('orders monitors by created date', function () {
        $newerMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'created_at' => now()->addMinute(),
        ]);
        $newerMonitor->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => true]);

        MonitorHistory::factory()->create([
            'monitor_id' => $newerMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now()->addMinute(),
        ]);

        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();

        $monitors = $response->json('data');
        expect($monitors[0]['id'])->toBe($newerMonitor->id);
    });

    it('handles monitors without history', function () {
        $monitorWithoutHistory = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitorWithoutHistory->users()->attach($this->user->id, ['is_active' => true, 'is_pinned' => true]);

        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();

        $data = $response->json('data');
        $hasMonitor = collect($data)->contains('id', $monitorWithoutHistory->id);
        expect($hasMonitor)->toBeTrue();
    });

    it('returns empty array when no pinned monitors exist', function () {
        // Unpin all monitors by detaching user relationships
        $this->user->monitors()->detach();

        $response = actingAs($this->user)->get('/pinned-monitors');

        $response->assertOk();
        $data = $response->json('data');
        expect($data)->toBeEmpty();
    });
});
