<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('LatestHistoryController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        // User owns the private monitor
        $this->privateMonitor->users()->attach($this->user->id, ['is_owner' => true]);
    });

    it('returns latest history for public monitor', function () {
        // Create multiple history entries
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'down',
            'response_time' => null,
            'created_at' => now()->subHours(2),
        ]);

        $latestHistory = MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'status_code' => 200,
            'created_at' => now(),
        ]);

        $response = get("/monitor/{$this->publicMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'monitor_id',
            'uptime_status',
            'response_time',
            'status_code',
            'checked_at',
            'created_at',
        ]);
        $response->assertJson([
            'id' => $latestHistory->id,
            'uptime_status' => 'up',
            'response_time' => 250,
        ]);
    });

    it('returns latest history for private monitor to owner', function () {
        $latestHistory = MonitorHistory::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 150,
            'created_at' => now(),
        ]);

        $response = actingAs($this->user)
            ->get("/monitor/{$this->privateMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJson([
            'id' => $latestHistory->id,
            'monitor_id' => $this->privateMonitor->id,
            'uptime_status' => 'up',
        ]);
    });

    it('prevents non-owner from viewing private monitor history', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $otherUser = User::factory()->create();

        $response = actingAs($otherUser)
            ->get("/monitor/{$this->privateMonitor->id}/latest-history");

        $response->assertForbidden();
    });

    it('returns null when monitor has no history', function () {
        $monitorWithoutHistory = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $response = get("/monitor/{$monitorWithoutHistory->id}/latest-history");

        $response->assertOk();
        $response->assertJson(null);
    });

    it('returns 404 for non-existent monitor', function () {
        $response = get('/monitor/999999/latest-history');

        $response->assertNotFound();
    });

    it('allows admin to view any monitor history', function () {
        $latestHistory = MonitorHistory::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'uptime_status' => 'down',
            'message' => 'Connection timeout',
            'created_at' => now(),
        ]);

        $response = actingAs($this->admin)
            ->get("/monitor/{$this->privateMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJson([
            'id' => $latestHistory->id,
            'uptime_status' => 'down',
            'message' => 'Connection timeout',
        ]);
    });

    it('returns only the most recent history entry', function () {
        // Create multiple history entries
        MonitorHistory::factory()->count(10)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->publicMonitor->id,
                'uptime_status' => $sequence->index % 2 === 0 ? 'up' : 'down',
                'response_time' => $sequence->index % 2 === 0 ? 200 : null,
                'created_at' => now()->subMinutes($sequence->index),
            ]
        )->create();

        $latestHistory = MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'recovery',
            'response_time' => 300,
            'created_at' => now()->addMinute(),
        ]);

        $response = get("/monitor/{$this->publicMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJson([
            'id' => $latestHistory->id,
            'uptime_status' => 'recovery',
        ]);
    });

    it('includes all history fields in response', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'down',
            'message' => 'SSL certificate expired',
            'response_time' => null,
            'status_code' => 495,
            'checked_at' => now()->subMinute(),
            'created_at' => now(),
        ]);

        $response = get("/monitor/{$this->publicMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'monitor_id',
            'uptime_status',
            'message',
            'response_time',
            'status_code',
            'checked_at',
            'created_at',
            'updated_at',
        ]);
    });

    it('works with disabled monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false,
        ]);

        $history = MonitorHistory::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get("/monitor/{$disabledMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJson([
            'id' => $history->id,
            'monitor_id' => $disabledMonitor->id,
        ]);
    });

    it('allows subscriber to view private monitor history', function () {
        $subscriber = User::factory()->create();
        $this->privateMonitor->users()->attach($subscriber->id, ['is_subscriber' => true]);

        $history = MonitorHistory::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = actingAs($subscriber)
            ->get("/monitor/{$this->privateMonitor->id}/latest-history");

        $response->assertOk();
        $response->assertJson([
            'id' => $history->id,
            'monitor_id' => $this->privateMonitor->id,
        ]);
    });
});
