<?php

use App\Models\Monitor;
use App\Models\MonitorStatistic;
use App\Models\StatusPage;
use App\Services\OgImageService;

beforeEach(function () {
    $this->service = new OgImageService;
});

it('generates a valid png for monitors index', function () {
    $png = $this->service->generateMonitorsIndex([
        'total' => 10,
        'up' => 8,
        'down' => 2,
    ]);

    expect($png)->not->toBeEmpty();
    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('generates a valid png for an up monitor', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'up',
    ]);

    $png = $this->service->generateMonitor($monitor);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('generates a valid png for a down monitor with statistics', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_status' => 'down',
    ]);
    MonitorStatistic::factory()->create([
        'monitor_id' => $monitor->id,
        'uptime_24h' => 95.0,
        'uptime_7d' => 90.0,
        'uptime_30d' => 85.0,
        'avg_response_time_24h' => 250,
    ]);
    $monitor->load('statistics');

    $png = $this->service->generateMonitor($monitor);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('handles long monitor hosts by truncating', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://'.str_repeat('a', 60).'.example.com',
        'uptime_status' => 'up',
    ]);

    $png = $this->service->generateMonitor($monitor);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('generates a valid png for a status page with all monitors up', function () {
    $statusPage = StatusPage::factory()->create([
        'title' => 'All Systems',
        'description' => 'Everything operational',
    ]);
    $upMonitor = Monitor::factory()->create(['uptime_status' => 'up']);
    $statusPage->monitors()->attach($upMonitor);
    $statusPage->load('monitors');

    $png = $this->service->generateStatusPage($statusPage);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('generates a valid png for a status page with mixed status', function () {
    $statusPage = StatusPage::factory()->create(['title' => 'Mixed']);
    $upMonitor = Monitor::factory()->create(['uptime_status' => 'up']);
    $downMonitor = Monitor::factory()->create(['uptime_status' => 'down']);
    $statusPage->monitors()->attach([$upMonitor->id, $downMonitor->id]);
    $statusPage->load('monitors');

    $png = $this->service->generateStatusPage($statusPage);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('generates a valid png for a status page with all monitors down', function () {
    $statusPage = StatusPage::factory()->create(['title' => 'Down']);
    $downMonitor = Monitor::factory()->create(['uptime_status' => 'down']);
    $statusPage->monitors()->attach($downMonitor);
    $statusPage->load('monitors');

    $png = $this->service->generateStatusPage($statusPage);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('generates a valid png for not found', function () {
    $png = $this->service->generateNotFound('unknown-domain.example.com');

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});

it('truncates long identifiers in not found image', function () {
    $png = $this->service->generateNotFound(str_repeat('x', 100).'.com');

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
});
