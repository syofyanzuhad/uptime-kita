<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use App\Services\OgImageService;

function mockOgImageService(): void
{
    test()->mock(OgImageService::class, function ($mock) {
        $mock->shouldReceive('generateMonitorsIndex')->andReturn('fake-png-data');
        $mock->shouldReceive('generateMonitor')->andReturn('fake-png-data');
        $mock->shouldReceive('generateStatusPage')->andReturn('fake-png-data');
        $mock->shouldReceive('generateNotFound')->andReturn('fake-not-found');
    });
}

test('generates monitors index image', function () {
    mockOgImageService();

    Monitor::factory()->create(['is_public' => true, 'uptime_status' => 'up']);

    $response = $this->get('/og/monitors.png');

    $response->assertOk();
    $response->assertHeader('content-type', 'image/png');
    $response->assertHeader('cache-control');
});

test('generates image for a public monitor', function () {
    mockOgImageService();

    $monitor = Monitor::factory()->create(['is_public' => true, 'url' => 'https://example.com']);

    $response = $this->get('/og/monitor/example.com.png');

    $response->assertOk();
    $response->assertHeader('content-type', 'image/png');
});

test('monitors index stats include all public monitors for non-admin users', function () {
    test()->mock(OgImageService::class, function ($mock) {
        $mock->shouldReceive('generateMonitorsIndex')
            ->withArgs(fn ($stats) => $stats['total'] === 1 && $stats['up'] === 1 && $stats['down'] === 0)
            ->andReturn('fake-png-data');
    });

    Monitor::factory()->create(['is_public' => true, 'uptime_status' => 'up']);

    $user = User::factory()->create(['is_admin' => false]);
    $response = $this->actingAs($user)->get('/og/monitors.png');

    $response->assertOk();
});

test('generates image for public monitor viewed by non-admin user', function () {
    test()->mock(OgImageService::class, function ($mock) {
        $mock->shouldReceive('generateMonitor')->andReturn('fake-png-data');
        $mock->shouldReceive('generateNotFound')->never();
    });

    Monitor::factory()->create(['is_public' => true, 'url' => 'https://example.com']);

    $user = User::factory()->create(['is_admin' => false]);
    $response = $this->actingAs($user)->get('/og/monitor/example.com.png');

    $response->assertOk();
    $response->assertHeader('content-type', 'image/png');
});

test('returns not found image for unknown monitor', function () {
    mockOgImageService();

    $response = $this->get('/og/monitor/unknown-domain.com.png');

    $response->assertOk();
    $response->assertHeader('content-type', 'image/png');
});

test('generates image for a status page', function () {
    mockOgImageService();

    StatusPage::factory()->create(['path' => 'my-status']);

    $response = $this->get('/og/status/my-status.png');

    $response->assertOk();
    $response->assertHeader('content-type', 'image/png');
});

test('returns not found image for unknown status page', function () {
    mockOgImageService();

    $response = $this->get('/og/status/does-not-exist.png');

    $response->assertOk();
    $response->assertHeader('content-type', 'image/png');
});
