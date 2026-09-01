<?php

use App\Models\TelemetryPing;
use Illuminate\Support\Carbon;

beforeEach(function () {
    TelemetryPing::query()->delete();
});

it('has fillable attributes and casts', function () {
    $ping = TelemetryPing::create([
        'instance_id' => 'abc-123',
        'app_version' => '1.0.0',
        'php_version' => '8.4',
        'laravel_version' => '13',
        'monitors_total' => 5,
        'users_total' => 2,
        'os_type' => 'Linux',
        'first_seen_at' => now(),
        'last_ping_at' => now(),
        'ping_count' => 3,
        'raw_data' => ['key' => 'value'],
    ]);

    expect($ping->instance_id)->toBe('abc-123');
    expect($ping->ping_count)->toBeInt();
    expect($ping->raw_data)->toBe(['key' => 'value']);
    expect($ping->first_seen_at)->toBeInstanceOf(Carbon::class);
});

it('scopes active instances to last N days', function () {
    TelemetryPing::create(['instance_id' => 'recent', 'last_ping_at' => now()->subDay()]);
    TelemetryPing::create(['instance_id' => 'old', 'last_ping_at' => now()->subDays(30)]);

    expect(TelemetryPing::active(7)->get())->toHaveCount(1);
    expect(TelemetryPing::active(7)->pluck('instance_id'))->toContain('recent');
});

it('scopes first seen between date range', function () {
    $now = Carbon::parse('2026-08-15 12:00:00');
    Carbon::setTestNow($now);

    TelemetryPing::create(['instance_id' => 'jan', 'first_seen_at' => $now->copy()->startOfMonth()->addDay()]);
    TelemetryPing::create(['instance_id' => 'old', 'first_seen_at' => $now->copy()->subMonths(3)]);

    expect(TelemetryPing::firstSeenBetween($now->copy()->startOfMonth(), $now)->get())->toHaveCount(1);

    Carbon::setTestNow(null);
});

it('computes statistics', function () {
    $now = Carbon::parse('2026-08-15 12:00:00');
    Carbon::setTestNow($now);

    TelemetryPing::create(['instance_id' => 'a', 'first_seen_at' => $now->copy()->startOfMonth(), 'last_ping_at' => $now->copy()->subDay()]);
    TelemetryPing::create(['instance_id' => 'b', 'first_seen_at' => $now->copy()->subMonths(2), 'last_ping_at' => $now->copy()->subMonths(2)]);

    $stats = TelemetryPing::getStatistics();

    expect($stats['total_instances'])->toBe(2);
    expect($stats['active_last_7_days'])->toBe(1);
    expect($stats['active_last_30_days'])->toBe(1);
    expect($stats['new_this_month'])->toBe(1);
    expect($stats['new_last_month'])->toBe(0);

    Carbon::setTestNow(null);
});

it('computes version distribution', function () {
    TelemetryPing::create(['instance_id' => 'a', 'app_version' => '1.0.0', 'php_version' => '8.4', 'laravel_version' => '13']);
    TelemetryPing::create(['instance_id' => 'b', 'app_version' => '1.0.0', 'php_version' => '8.3', 'laravel_version' => '12']);
    TelemetryPing::create(['instance_id' => 'c', 'app_version' => null, 'php_version' => null, 'laravel_version' => null]);

    $dist = TelemetryPing::getVersionDistribution();

    expect($dist['app'])->toBe(['1.0.0' => 2]);
    expect($dist['php'])->toBe(['8.4' => 1, '8.3' => 1]);
    expect($dist['laravel'])->toHaveCount(2);
});

it('computes os distribution', function () {
    TelemetryPing::create(['instance_id' => 'a', 'os_type' => 'Linux']);
    TelemetryPing::create(['instance_id' => 'b', 'os_type' => 'Linux']);
    TelemetryPing::create(['instance_id' => 'c', 'os_type' => null]);

    expect(TelemetryPing::getOsDistribution())->toBe(['Linux' => 2]);
});

it('computes growth data for charts', function () {
    TelemetryPing::create(['instance_id' => 'a', 'first_seen_at' => now()->startOfMonth()]);

    $growth = TelemetryPing::getGrowthData(2);

    expect($growth)->toHaveCount(2);
    expect($growth[0]['month'])->toBe(now()->subMonth()->format('M Y'));
    expect($growth[1]['month'])->toBe(now()->format('M Y'));
    expect($growth[1]['count'])->toBe(1);
});
