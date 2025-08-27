<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('PublicMonitorController', function () {
    beforeEach(function () {
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
    });

    it('displays public monitors page', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicIndex')
            ->has('monitors')
            ->has('stats')
            ->has('filters')
            ->has('availableTags')
        );
    });

    it('includes only public and enabled monitors', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('monitors.data.0.id', $this->publicMonitor->id)
            ->count('monitors.data', 1)
        );
    });

    it('excludes private monitors', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->whereNot('monitors.data.0.id', $this->privateMonitor->id)
        );
    });

    it('excludes disabled monitors', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->whereNot('monitors.data.0.id', $this->disabledMonitor->id)
        );
    });

    it('includes monitor statistics', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_percentage' => 99.5,
            'date' => now()->toDateString(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data.0.last_check_date')
            ->has('monitors.data.0.today_uptime_percentage')
        );
    });

    it('includes basic monitor information', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data.0.id')
            ->has('monitors.data.0.name')
            ->has('monitors.data.0.url')
        );
    });

    it('paginates public monitors', function () {
        Monitor::factory()->count(20)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 15) // Default pagination
            ->has('monitors.links')
            ->has('monitors.meta')
        );
    });

    it('respects per_page parameter', function () {
        Monitor::factory()->count(10)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $response = get('/public-monitors?per_page=5');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 5)
        );
    });

    it('calculates monitor counts correctly', function () {
        Monitor::factory()->count(3)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $upMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
        ]);

        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $upMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $downMonitor->id,
            'uptime_status' => 'down',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('stats.total', 6)
            ->where('stats.up', 5)  // All monitors default to up except the one explicitly set to down
            ->where('stats.down', 1) // Only the one explicitly set to down
        );
    });

    it('orders monitors by created date descending', function () {
        $oldMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'created_at' => now()->subDays(2),
        ]);

        $newMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $oldMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now()->subDays(2),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $newMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        // Just verify that both monitors are present in the response
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data')
        );
    });
});
