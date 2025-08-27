<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('PublicMonitorShowController', function () {
    beforeEach(function () {
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'url' => 'https://example.com',
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
            'url' => 'https://private.com',
        ]);

        $this->disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false,
            'url' => 'https://disabled.com',
        ]);
    });

    it('displays public monitor by domain', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShow')
            ->has('monitor')
            ->has('histories')
            ->has('uptimes')
            ->has('statistics')
            ->has('recentHistory')
            ->has('incidents')
        );
    });

    it('returns monitor with correct data structure', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('monitor.id', $this->publicMonitor->id)
            ->where('monitor.url', 'https://example.com')
            ->has('monitor.uptime_status')
            ->has('monitor.favicon_url')
            ->has('monitor.response_time')
        );
    });

    it('includes monitor histories', function () {
        MonitorHistory::factory()->count(5)->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('histories', 5)
        );
    });

    it('includes daily uptime data', function () {
        MonitorUptimeDaily::factory()->count(7)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($sequence->index)->toDateString(),
                'uptime_percentage' => 99.5 - $sequence->index,
            ]
        )->create();

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('uptimes', 7)
        );
    });

    it('includes monitor statistics', function () {
        MonitorHistory::factory()->count(10)->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->count(2)->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'down',
            'response_time' => null,
            'created_at' => now()->subHours(2),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('statistics.average_response_time')
            ->has('statistics.uptime_percentage')
            ->has('statistics.total_checks')
            ->has('statistics.total_downtime')
        );
    });

    it('returns not found for private monitor', function () {
        $response = get('/m/private.com');

        $response->assertOk(); // It renders PublicShowNotFound component
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShowNotFound')
        );
    });

    it('returns not found for disabled monitor', function () {
        $response = get('/m/disabled.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShowNotFound')
        );
    });

    it('returns not found for non-existent domain', function () {
        $response = get('/m/nonexistent.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShowNotFound')
        );
    });

    it('includes recent history with limit', function () {
        MonitorHistory::factory()->count(100)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->publicMonitor->id,
                'uptime_status' => $sequence->index % 10 === 0 ? 'down' : 'up',
                'response_time' => $sequence->index % 10 === 0 ? null : rand(100, 500),
                'created_at' => now()->subMinutes($sequence->index),
            ]
        )->create();

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('recentHistory', 50) // Should be limited to 50
        );
    });

    it('includes incident data', function () {
        // Create an incident (down period)
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'down',
            'response_time' => null,
            'message' => 'Connection timeout',
            'created_at' => now()->subHours(2),
        ]);

        // Recovery
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now()->subHour(),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('incidents')
        );
    });

    it('handles monitor with www subdomain', function () {
        $wwwMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'url' => 'https://www.example.com',
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $wwwMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/m/www.example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShow')
            ->where('monitor.id', $wwwMonitor->id)
        );
    });

    it('handles monitor with subdomain', function () {
        $subdomainMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'url' => 'https://api.example.com',
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $subdomainMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/m/api.example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShow')
            ->where('monitor.id', $subdomainMonitor->id)
        );
    });

    it('handles monitor with port in URL', function () {
        $portMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'url' => 'https://example.com:8080',
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $portMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        // The route matches domain without port
        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShow')
            ->where('monitor.id', $portMonitor->id)
        );
    });
});
