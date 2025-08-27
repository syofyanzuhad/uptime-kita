<?php

use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('UptimesDailyController', function () {
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

        // Create daily uptime data for the past 30 days
        for ($i = 0; $i < 30; $i++) {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($i)->toDateString(),
                'uptime_percentage' => 99.5 - ($i * 0.1), // Gradually decreasing uptime
                'total_checks' => 1440, // 1 check per minute
                'successful_checks' => round(1440 * (99.5 - ($i * 0.1)) / 100),
                'average_response_time' => 200 + ($i * 5),
            ]);
        }
    });

    it('returns daily uptimes for public monitor', function () {
        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => [
                'id',
                'monitor_id',
                'date',
                'uptime_percentage',
                'total_checks',
                'successful_checks',
                'average_response_time',
            ],
        ]);
    });

    it('returns uptimes ordered by date descending', function () {
        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

        $response->assertOk();

        $uptimes = $response->json();

        // Check that dates are in descending order
        for ($i = 0; $i < count($uptimes) - 1; $i++) {
            $currentDate = $uptimes[$i]['date'];
            $nextDate = $uptimes[$i + 1]['date'];

            expect($currentDate)->toBeGreaterThan($nextDate);
        }
    });

    it('limits results to 30 days by default', function () {
        // Create more than 30 days of data
        for ($i = 30; $i < 60; $i++) {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($i)->toDateString(),
                'uptime_percentage' => 95.0,
            ]);
        }

        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJsonCount(30);
    });

    it('allows custom limit parameter', function () {
        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=7");

        $response->assertOk();
        $response->assertJsonCount(7);
    });

    it('returns daily uptimes for private monitor to owner', function () {
        // Create uptime data for private monitor
        MonitorUptimeDaily::factory()->count(7)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->privateMonitor->id,
                'date' => now()->subDays($sequence->index)->toDateString(),
                'uptime_percentage' => 100.0,
            ]
        )->create();

        $response = actingAs($this->user)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJsonCount(7);
    });

    it('prevents non-owner from viewing private monitor uptimes', function () {
        $otherUser = User::factory()->create();

        $response = actingAs($otherUser)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertForbidden();
    });

    it('allows admin to view any monitor uptimes', function () {
        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'date' => now()->toDateString(),
            'uptime_percentage' => 98.5,
        ]);

        $response = actingAs($this->admin)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertOk();
    });

    it('returns empty array when monitor has no uptime data', function () {
        $monitorWithoutData = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $response = get("/monitor/{$monitorWithoutData->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJson([]);
    });

    it('returns 404 for non-existent monitor', function () {
        $response = get('/monitor/999999/uptimes-daily');

        $response->assertNotFound();
    });

    it('includes all uptime metrics in response', function () {
        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=1");

        $response->assertOk();

        $uptime = $response->json()[0];

        expect($uptime)->toHaveKeys([
            'id',
            'monitor_id',
            'date',
            'uptime_percentage',
            'total_checks',
            'successful_checks',
            'average_response_time',
        ]);

        expect($uptime['uptime_percentage'])->toBeFloat();
        expect($uptime['total_checks'])->toBeInt();
        expect($uptime['successful_checks'])->toBeInt();
    });

    it('works with disabled monitors', function () {
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false,
        ]);

        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'date' => now()->toDateString(),
            'uptime_percentage' => 99.0,
        ]);

        $response = get("/monitor/{$disabledMonitor->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJsonCount(1);
    });

    it('allows subscriber to view private monitor uptimes', function () {
        $subscriber = User::factory()->create();
        $this->privateMonitor->users()->attach($subscriber->id, ['is_subscriber' => true]);

        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'date' => now()->toDateString(),
            'uptime_percentage' => 100.0,
        ]);

        $response = actingAs($subscriber)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertOk();
    });

    it('handles invalid limit parameter gracefully', function () {
        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=invalid");

        $response->assertOk();
        $response->assertJsonCount(30); // Should use default limit
    });

    it('handles negative limit parameter', function () {
        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=-5");

        $response->assertOk();
        $response->assertJsonCount(30); // Should use default limit
    });

    it('caps limit to reasonable maximum', function () {
        // Create 100 days of data
        for ($i = 30; $i < 100; $i++) {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($i)->toDateString(),
                'uptime_percentage' => 95.0,
            ]);
        }

        $response = get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=1000");

        $response->assertOk();

        $count = count($response->json());
        expect($count)->toBeLessThanOrEqual(365); // Should cap at 1 year max
    });
});
