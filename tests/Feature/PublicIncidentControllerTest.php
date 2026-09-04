<?php

use App\Models\Monitor;
use App\Models\MonitorIncident;

use function Pest\Laravel\get;

describe('PublicIncidentController', function () {
    beforeEach(function () {
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'display_name' => 'Acme Public App',
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
            'display_name' => 'Secret Internal Service',
        ]);
    });

    it('displays public incidents page', function () {
        $response = get('/incidents');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('incidents/PublicIndex')
            ->has('incidents')
            ->has('filters')
            ->has('stats')
        );
    });

    it('includes incidents from public monitors only', function () {
        $publicIncident = MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'duration_minutes' => 60,
        ]);

        $privateIncident = MonitorIncident::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHour(),
            'duration_minutes' => 120,
        ]);

        $response = get('/incidents');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('incidents.data.0.id', $publicIncident->id)
            ->count('incidents.data', 1)
        );
    });

    it('filters ongoing incidents', function () {
        $ongoing = MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
            'duration_minutes' => null,
        ]);

        $resolved = MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subDays(2),
            'ended_at' => now()->subDays(2)->addHour(),
            'duration_minutes' => 60,
        ]);

        $response = get('/incidents?status=ongoing');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->count('incidents.data', 1)
            ->where('incidents.data.0.id', $ongoing->id)
        );
    });

    it('filters resolved incidents', function () {
        $ongoing = MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
        ]);

        $resolved = MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subDays(1),
            'ended_at' => now()->subDays(1)->addMinutes(45),
            'duration_minutes' => 45,
        ]);

        $response = get('/incidents?status=resolved');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->count('incidents.data', 1)
            ->where('incidents.data.0.id', $resolved->id)
        );
    });

    it('searches incidents by monitor name', function () {
        $monitorTwo = Monitor::factory()->create([
            'is_public' => true,
            'display_name' => 'Beta Service',
        ]);

        $incidentOne = MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subHours(2),
        ]);

        $incidentTwo = MonitorIncident::factory()->create([
            'monitor_id' => $monitorTwo->id,
            'started_at' => now()->subHours(1),
        ]);

        $response = get('/incidents?search=Beta');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->count('incidents.data', 1)
            ->where('incidents.data.0.id', $incidentTwo->id)
        );
    });

    it('calculates incident statistics correctly', function () {
        MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subMinutes(15),
            'ended_at' => null,
            'duration_minutes' => null,
        ]);

        MonitorIncident::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'started_at' => now()->subDays(5),
            'ended_at' => now()->subDays(5)->addMinutes(30),
            'duration_minutes' => 30,
        ]);

        $response = get('/incidents');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('stats.ongoing_count', 1)
            ->where('stats.resolved_30d', 1)
            ->where('stats.avg_duration_minutes', 30)
            ->where('stats.total_public_monitors', 1)
        );
    });
});
