<?php

use App\Models\Monitor;
use App\Models\MonitorStatistic;

use function Pest\Laravel\get;

describe('BadgeController', function () {
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

    it('returns SVG badge for public monitor', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 99.5,
            'uptime_7d' => 99.8,
            'uptime_30d' => 99.9,
            'uptime_90d' => 99.95,
        ]);

        $response = get('/badge/example.com');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        expect($response->getContent())->toContain('<svg');
        expect($response->getContent())->toContain('99.5%');
        expect($response->getContent())->toContain('uptime');
    });

    it('returns not found badge for private monitor', function () {
        $response = get('/badge/private.com');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        expect($response->getContent())->toContain('<svg');
        expect($response->getContent())->toContain('not found');
    });

    it('returns not found badge for disabled monitor', function () {
        $response = get('/badge/disabled.com');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        expect($response->getContent())->toContain('not found');
    });

    it('returns not found badge for non-existent domain', function () {
        $response = get('/badge/nonexistent.com');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        expect($response->getContent())->toContain('not found');
    });

    it('supports period query parameter for 7d', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 95.0,
            'uptime_7d' => 98.5,
            'uptime_30d' => 99.0,
            'uptime_90d' => 99.5,
        ]);

        $response = get('/badge/example.com?period=7d');

        $response->assertOk();
        expect($response->getContent())->toContain('98.5%');
    });

    it('supports period query parameter for 30d', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 95.0,
            'uptime_7d' => 98.5,
            'uptime_30d' => 99.2,
            'uptime_90d' => 99.5,
        ]);

        $response = get('/badge/example.com?period=30d');

        $response->assertOk();
        expect($response->getContent())->toContain('99.2%');
    });

    it('supports custom label query parameter', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 99.0,
        ]);

        $response = get('/badge/example.com?label=status');

        $response->assertOk();
        expect($response->getContent())->toContain('status');
    });

    it('supports flat-square style', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 99.0,
        ]);

        $response = get('/badge/example.com?style=flat-square');

        $response->assertOk();
        // flat-square has rx="0" (no rounded corners)
        expect($response->getContent())->toContain('rx="0"');
    });

    it('supports plastic style', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 99.0,
        ]);

        $response = get('/badge/example.com?style=plastic');

        $response->assertOk();
        // plastic style includes gradient definition
        expect($response->getContent())->toContain('id="gradient"');
    });

    it('shows green color for uptime >= 99%', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 99.5,
        ]);

        $response = get('/badge/example.com');

        $response->assertOk();
        expect($response->getContent())->toContain('#4c1'); // brightgreen
    });

    it('shows yellow color for uptime between 90-95%', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 92.0,
        ]);

        $response = get('/badge/example.com');

        $response->assertOk();
        expect($response->getContent())->toContain('#dfb317'); // yellow
    });

    it('shows red color for uptime below 80%', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 75.0,
        ]);

        $response = get('/badge/example.com');

        $response->assertOk();
        expect($response->getContent())->toContain('#e05d44'); // red
    });

    it('sets proper cache headers', function () {
        MonitorStatistic::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_24h' => 99.0,
        ]);

        $response = get('/badge/example.com');

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=300, public, s-maxage=300');
    });

    it('handles subdomain correctly', function () {
        $subdomainMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://api.example.com',
        ]);

        MonitorStatistic::factory()->create([
            'monitor_id' => $subdomainMonitor->id,
            'uptime_24h' => 99.0,
        ]);

        $response = get('/badge/api.example.com');

        $response->assertOk();
        expect($response->getContent())->toContain('99.0%');
    });

    it('defaults to 100% uptime when no statistics exist', function () {
        // No statistics created for this monitor

        $response = get('/badge/example.com');

        $response->assertOk();
        expect($response->getContent())->toContain('100.0%');
    });
});
