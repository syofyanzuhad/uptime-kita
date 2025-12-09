<?php

use App\Models\User;
use App\Services\ServerResourceService;

test('server resources page requires authentication', function () {
    $response = $this->get('/settings/server-resources');

    $response->assertRedirect('/login');
});

test('authenticated user can access server resources page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/server-resources');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/ServerResources')
        ->has('initialMetrics')
    );
});

test('server resources api requires authentication', function () {
    $response = $this->getJson('/api/server-resources');

    $response->assertUnauthorized();
});

test('authenticated user can access server resources api', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/server-resources');

    $response->assertOk();
    $response->assertJsonStructure([
        'cpu' => ['usage_percent', 'cores'],
        'memory' => ['total', 'used', 'free', 'usage_percent', 'total_formatted', 'used_formatted', 'free_formatted'],
        'disk' => ['total', 'used', 'free', 'usage_percent', 'total_formatted', 'used_formatted', 'free_formatted'],
        'uptime' => ['seconds', 'formatted'],
        'load_average' => ['1min', '5min', '15min'],
        'php' => ['version', 'memory_limit', 'current_memory_formatted'],
        'laravel' => ['version', 'environment', 'debug_mode'],
        'database' => ['connection', 'status', 'size_formatted'],
        'queue' => ['driver', 'pending_jobs', 'failed_jobs'],
        'cache' => ['driver', 'status'],
        'timestamp',
    ]);
});

test('server resource service returns valid metrics', function () {
    $service = new ServerResourceService;
    $metrics = $service->getMetrics();

    expect($metrics)->toHaveKeys([
        'cpu',
        'memory',
        'disk',
        'uptime',
        'load_average',
        'php',
        'laravel',
        'database',
        'queue',
        'cache',
        'timestamp',
    ]);

    expect($metrics['cpu']['usage_percent'])->toBeGreaterThanOrEqual(0);
    expect($metrics['cpu']['cores'])->toBeGreaterThan(0);
    expect($metrics['memory']['total'])->toBeGreaterThan(0);
    expect($metrics['disk']['total'])->toBeGreaterThan(0);
    expect($metrics['php']['version'])->toBeString();
    expect($metrics['laravel']['version'])->toBeString();
});
