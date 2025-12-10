<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;

it('displays daily checks count for public monitors', function () {
    // Create public monitors
    $publicMonitor1 = Monitor::factory()->create(['is_public' => true]);
    $publicMonitor2 = Monitor::factory()->create(['is_public' => true]);
    $privateMonitor = Monitor::factory()->create(['is_public' => false]);

    // Create monitor histories for today
    MonitorHistory::factory()->count(5)->create([
        'monitor_id' => $publicMonitor1->id,
        'checked_at' => now(),
    ]);

    MonitorHistory::factory()->count(3)->create([
        'monitor_id' => $publicMonitor2->id,
        'checked_at' => now(),
    ]);

    // This shouldn't be counted (private monitor)
    MonitorHistory::factory()->count(2)->create([
        'monitor_id' => $privateMonitor->id,
        'checked_at' => now(),
    ]);

    // This shouldn't be counted (yesterday)
    MonitorHistory::factory()->count(4)->create([
        'monitor_id' => $publicMonitor1->id,
        'checked_at' => now()->subDay(),
    ]);

    $response = $this->get('/public-monitors');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('monitors/PublicIndex')
        ->has('stats.daily_checks')
        ->where('stats.daily_checks', 8) // 5 + 3 from public monitors today
    );
});

it('returns zero when no checks exist for today', function () {
    // Create public monitors without any histories
    Monitor::factory()->count(2)->create(['is_public' => true]);

    $response = $this->get('/public-monitors');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('monitors/PublicIndex')
        ->has('stats.daily_checks')
        ->where('stats.daily_checks', 0)
    );
});

it('uses monitor_statistics table when available', function () {
    // Create public monitors
    $publicMonitor1 = Monitor::factory()->create(['is_public' => true]);
    $publicMonitor2 = Monitor::factory()->create(['is_public' => true]);

    // Create monitor statistics with total_checks_24h
    DB::table('monitor_statistics')->insert([
        'monitor_id' => $publicMonitor1->id,
        'total_checks_24h' => 100,
        'calculated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('monitor_statistics')->insert([
        'monitor_id' => $publicMonitor2->id,
        'total_checks_24h' => 50,
        'calculated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/public-monitors');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('monitors/PublicIndex')
        ->has('stats.daily_checks')
        ->where('stats.daily_checks', 150) // 100 + 50 from statistics
    );
});

it('caches daily checks count for performance', function () {
    $publicMonitor = Monitor::factory()->create(['is_public' => true]);

    MonitorHistory::factory()->count(5)->create([
        'monitor_id' => $publicMonitor->id,
        'checked_at' => now(),
    ]);

    // First request
    $response1 = $this->get('/public-monitors');
    $response1->assertOk();

    // Add more histories
    MonitorHistory::factory()->count(3)->create([
        'monitor_id' => $publicMonitor->id,
        'checked_at' => now(),
    ]);

    // Second request should still show cached value
    $response2 = $this->get('/public-monitors');
    $response2->assertInertia(fn ($page) => $page
        ->where('stats.daily_checks', 5) // Still 5 due to cache
    );

    // Clear cache and check again
    cache()->forget('public_monitors_daily_checks');

    $response3 = $this->get('/public-monitors');
    $response3->assertInertia(fn ($page) => $page
        ->where('stats.daily_checks', 8) // Now shows updated count
    );
});
