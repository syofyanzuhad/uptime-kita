<?php

use App\Models\Monitor;
use App\Models\User;

test('monitor can have tags attached', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    $monitor->attachTags(['production', 'api', 'critical']);

    expect($monitor->tags->count())->toBe(3);
    expect($monitor->tags->pluck('name')->toArray())->toContain('production', 'api', 'critical');
});

test('monitor can be filtered by tags', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $monitor1 = Monitor::factory()->create(['url' => 'https://example1.com']);
    $monitor2 = Monitor::factory()->create(['url' => 'https://example2.com']);
    $monitor3 = Monitor::factory()->create(['url' => 'https://example3.com']);

    $monitor1->attachTags(['production']);
    $monitor2->attachTags(['staging']);
    $monitor3->attachTags(['production', 'api']);

    $productionMonitors = Monitor::withoutGlobalScopes()->withAnyTags(['production'])->get();
    $stagingMonitors = Monitor::withoutGlobalScopes()->withAnyTags(['staging'])->get();

    expect($productionMonitors->count())->toBe(2);
    expect($stagingMonitors->count())->toBe(1);
    expect($productionMonitors->pluck('id'))->toContain($monitor1->id, $monitor3->id);
    expect($stagingMonitors->pluck('id'))->toContain($monitor2->id);
});

test('tag endpoint returns tags for monitors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $monitor = Monitor::factory()->create();
    $monitor->attachTags(['production', 'api']);

    $response = $this->get('/tags');

    $response->assertOk();
    $response->assertJsonStructure([
        'tags' => [
            '*' => ['id', 'name', 'type']
        ]
    ]);
});
