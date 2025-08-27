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
    });

    it('displays public monitors page', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicIndex')
            ->has('publicMonitors')
            ->has('pinnedMonitors')
            ->has('totalMonitors')
            ->has('upMonitors')
            ->has('downMonitors')
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
            ->where('publicMonitors.data.0.id', $this->publicMonitor->id)
            ->count('publicMonitors.data', 1)
        );
    });

    it('excludes private monitors', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->whereNot('publicMonitors.data.0.id', $this->privateMonitor->id)
        );
    });

    it('excludes disabled monitors', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->whereNot('publicMonitors.data.0.id', $this->disabledMonitor->id)
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
            ->has('publicMonitors.data.0.uptime_last_check_date')
            ->has('publicMonitors.data.0.today_uptime_percentage')
            ->has('publicMonitors.data.0.favicon_url')
            ->has('publicMonitors.data.0.response_time')
        );
    });

    it('handles pinned monitors', function () {
        $pinnedMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'is_pinned' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $pinnedMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('pinnedMonitors.0.id', $pinnedMonitor->id)
            ->count('pinnedMonitors', 1)
        );
    });

    it('paginates public monitors', function () {
        Monitor::factory()->count(20)->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('publicMonitors.data', 15) // Default pagination
            ->has('publicMonitors.links')
            ->has('publicMonitors.meta')
        );
    });

    it('respects per_page parameter', function () {
        Monitor::factory()->count(10)->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $response = get('/public-monitors?per_page=5');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('publicMonitors.data', 5)
        );
    });

    it('calculates monitor counts correctly', function () {
        Monitor::factory()->count(3)->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $upMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
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
            ->where('totalMonitors', 5)
            ->where('upMonitors', 1)
            ->where('downMonitors', 1)
        );
    });

    it('orders monitors by created date descending', function () {
        $oldMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'created_at' => now()->subDays(2),
        ]);

        $newMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
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
        $response->assertInertia(fn ($page) => $page
            ->where('publicMonitors.data.0.id', $newMonitor->id)
            ->where('publicMonitors.data.1.id', $oldMonitor->id)
        );
    });
});
