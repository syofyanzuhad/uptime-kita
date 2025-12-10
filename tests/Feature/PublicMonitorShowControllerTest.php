<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;

use function Pest\Laravel\get;

describe('PublicMonitorShowController', function () {
    beforeEach(function () {
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://example.com',
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
            'url' => 'https://private.com',
        ]);

        $this->disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
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
            ->has('uptimeStats')
            ->has('responseTimeStats')
            ->has('latestIncidents')
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
            ->has('monitor')
            ->has('histories')
            ->has('uptimeStats')
            ->has('responseTimeStats')
        );
    });

    it('includes monitor histories', function () {
        // Create 5 histories with different timestamps within the last 100 minutes
        MonitorHistory::factory()->count(5)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->publicMonitor->id,
                'uptime_status' => 'up',
                'response_time' => 250,
                'created_at' => now()->subMinutes($sequence->index * 10),
            ]
        )->create();

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('histories', 5)
        );
    });

    it('includes uptime statistics', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('uptimeStats.24h')
            ->has('uptimeStats.7d')
            ->has('uptimeStats.30d')
            ->has('uptimeStats.90d')
        );
    });

    it('includes response time statistics', function () {
        MonitorHistory::factory()->count(10)->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        $response = get('/m/example.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('responseTimeStats.average')
            ->has('responseTimeStats.min')
            ->has('responseTimeStats.max')
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

    it('includes histories with proper limit', function () {
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
            ->has('histories') // Histories from last 100 minutes
        );
    });

    it('includes latest incidents data', function () {
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
            ->has('latestIncidents')
        );
    });

    it('handles monitor with www subdomain', function () {
        $wwwMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
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
            ->has('monitor')
        );
    });

    it('handles monitor with subdomain', function () {
        $subdomainMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
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
            ->has('monitor')
        );
    });

    it('handles monitor with port in URL', function () {
        $portMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://portexample.com:8080',
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $portMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        // The route matches domain without port - but controller won't find it
        // since it's looking for 'https://portexample.com' not 'https://portexample.com:8080'
        $response = get('/m/portexample.com');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShowNotFound')
        );
    });
});
