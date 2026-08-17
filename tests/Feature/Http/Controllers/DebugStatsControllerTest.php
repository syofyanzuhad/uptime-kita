<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('returns stats for authenticated user', function () {
    $monitor = Monitor::factory()->create(['is_public' => true]);
    $this->user->monitors()->attach($monitor->id, [
        'is_active' => true,
        'is_pinned' => false,
    ]);
    MonitorHistory::factory()->create([
        'monitor_id' => $monitor->id,
        'checked_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->getJson('/debug-stats');

    $response->assertOk();
    $response->assertJsonStructure([
        'timezone',
        'current_time',
        'today',
        'cache_keys' => ['daily_checks', 'monthly_checks'],
        'public_monitors_count',
        'monitor_statistics' => ['total_rows', 'sum_24h', 'sum_30d'],
        'monitor_histories' => ['total_rows', 'public_monitor_checks_today', 'public_monitor_checks_this_month', 'latest_check'],
        'raw_queries' => ['daily_direct', 'monthly_direct'],
    ]);

    expect($response->json('public_monitors_count'))->toBe(1);
    expect($response->json('monitor_histories.total_rows'))->toBe(1);
});

test('public monitor count includes all public monitors for non-admin users', function () {
    // Not attached to the authenticated user, so the user global scope would hide it.
    Monitor::factory()->create(['is_public' => true]);

    $response = $this->actingAs($this->user)->getJson('/debug-stats');

    $response->assertOk();
    expect($response->json('public_monitors_count'))->toBe(1);
});

test('requires authentication', function () {
    $response = $this->getJson('/debug-stats');

    $response->assertUnauthorized();
});

test('clears cache when requested', function () {
    cache()->put('public_monitors_daily_checks', 'cached');
    cache()->put('public_monitors_monthly_checks', 'cached');

    $response = $this->actingAs($this->user)->getJson('/debug-stats?clear_cache=1');

    $response->assertOk();
    expect($response->json('cache_cleared'))->toBeTrue();
    expect(cache()->has('public_monitors_daily_checks'))->toBeFalse();
    expect(cache()->has('public_monitors_monthly_checks'))->toBeFalse();
});

test('returns zero counts when no monitors exist', function () {
    $response = $this->actingAs($this->user)->getJson('/debug-stats');

    $response->assertOk();
    expect($response->json('public_monitors_count'))->toBe(0);
    expect($response->json('monitor_histories.total_rows'))->toBe(0);
});
