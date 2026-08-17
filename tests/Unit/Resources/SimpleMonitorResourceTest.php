<?php

use App\Http\Resources\SimpleMonitorResource;
use App\Models\Monitor;
use App\Models\MonitorStatistic;
use App\Models\MonitorUptimeDaily;
use Spatie\Tags\Tag;

it('serializes a monitor with basic fields', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://www.example.com',
        'uptime_status' => 'up',
        'uptime_check_enabled' => true,
        'uptime_last_check_date' => now(),
    ]);

    $resource = (new SimpleMonitorResource($monitor))->resolve();

    expect($resource['id'])->toBe($monitor->id);
    expect($resource['url'])->toBe('https://www.example.com');
    expect($resource['host'])->toBe('example.com');
    expect($resource['uptime_status'])->toBe('up');
    expect($resource['uptime_check_enabled'])->toBeTrue();
    expect($resource['favicon'])->toContain('example.com');
    expect($resource['last_check_date'])->not->toBeNull();
    expect($resource['today_uptime_percentage'])->toBe(0.0);
});

it('uses uptime daily for today uptime percentage when loaded', function () {
    $monitor = Monitor::factory()->create();
    $daily = MonitorUptimeDaily::factory()->create([
        'monitor_id' => $monitor->id,
        'uptime_percentage' => 99.5,
    ]);
    $monitor->setRelation('uptimeDaily', $daily);

    $resource = (new SimpleMonitorResource($monitor))->resolve();

    expect($resource['today_uptime_percentage'])->toBe(99.5);
});

it('falls back to statistics uptime when daily is not loaded', function () {
    $monitor = Monitor::factory()->create();
    $stats = MonitorStatistic::factory()->create([
        'monitor_id' => $monitor->id,
        'uptime_24h' => 98.0,
    ]);
    $monitor->setRelation('statistics', $stats);

    $resource = (new SimpleMonitorResource($monitor))->resolve();

    expect($resource['today_uptime_percentage'])->toBe(98.0);
    expect($resource['statistics']['uptime_24h'])->toBe(98.0);
});

it('serializes tags when loaded', function () {
    $monitor = Monitor::factory()->create();
    $tag = Tag::findOrCreate('production');
    $monitor->attachTag($tag);
    $monitor->load('tags');

    $resource = (new SimpleMonitorResource($monitor))->resolve();

    expect($resource['tags'])->toHaveCount(1);
    expect($resource['tags'][0]['name'])->toBe('production');
});

it('handles monitors with www prefix and no protocol', function () {
    $monitor = Monitor::factory()->create(['url' => 'http://www.example.org/path']);

    $resource = (new SimpleMonitorResource($monitor))->resolve();

    expect($resource['host'])->toBe('example.org');
});
