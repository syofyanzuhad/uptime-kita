<?php

use App\Jobs\CheckMonitorBatchJob;
use App\Models\Monitor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\post;

beforeEach(function () {
    Queue::fake();
    Cache::flush();

    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_public' => true,
        'uptime_check_enabled' => true,
    ]);

    $this->privateMonitor = Monitor::factory()->create([
        'url' => 'https://private.com',
        'is_public' => false,
        'uptime_check_enabled' => true,
    ]);

    $this->disabledMonitor = Monitor::factory()->create([
        'url' => 'https://disabled.com',
        'is_public' => true,
        'uptime_check_enabled' => false,
    ]);
});

it('dispatches a check job for a public monitor', function () {
    $response = post('/api/monitor/example.com/check');

    $response->assertStatus(202);
    $response->assertJson(['message' => 'Uptime check queued. Results will appear shortly.']);

    Queue::assertPushed(CheckMonitorBatchJob::class, function ($job) {
        return $job->monitorIds === [$this->monitor->id];
    });
});

it('returns 202 with retry_after seconds', function () {
    $response = post('/api/monitor/example.com/check');

    $response->assertStatus(202);
    $response->assertJsonStructure(['message', 'retry_after']);
    expect($response->json('retry_after'))->toBe(60);
});

it('returns 404 for a private monitor', function () {
    $response = post('/api/monitor/private.com/check');

    $response->assertNotFound();
    Queue::assertNothingPushed();
});

it('returns 404 for a disabled monitor', function () {
    $response = post('/api/monitor/disabled.com/check');

    $response->assertNotFound();
    Queue::assertNothingPushed();
});

it('returns 404 for a non-existent domain', function () {
    $response = post('/api/monitor/nonexistent.com/check');

    $response->assertNotFound();
    Queue::assertNothingPushed();
});

it('returns 429 when per-monitor cooldown is active', function () {
    // First request \u2014 succeeds
    post('/api/monitor/example.com/check')->assertStatus(202);

    // Second request within cooldown window \u2014 blocked
    $response = post('/api/monitor/example.com/check');

    $response->assertStatus(429);
    $response->assertJsonStructure(['message', 'retry_after']);

    // Only one job should have been dispatched
    Queue::assertPushed(CheckMonitorBatchJob::class, 1);
});

it('busts the monitor history cache on successful check', function () {
    Cache::put("public_monitor_{$this->monitor->id}_100m_histories", ['cached'], 60);

    post('/api/monitor/example.com/check')->assertStatus(202);

    expect(Cache::has("public_monitor_{$this->monitor->id}_100m_histories"))->toBeFalse();
});

it('sets a cooldown cache entry after successful check', function () {
    post('/api/monitor/example.com/check')->assertStatus(202);

    expect(Cache::has("manual_check_cooldown_{$this->monitor->id}"))->toBeTrue();
});

it('allows a check after the cooldown expires', function () {
    post('/api/monitor/example.com/check')->assertStatus(202);

    // Simulate cooldown expiry
    Cache::forget("manual_check_cooldown_{$this->monitor->id}");
    Cache::forget("manual_check_cooldown_{$this->monitor->id}_ttl");

    post('/api/monitor/example.com/check')->assertStatus(202);

    Queue::assertPushed(CheckMonitorBatchJob::class, 2);
});
