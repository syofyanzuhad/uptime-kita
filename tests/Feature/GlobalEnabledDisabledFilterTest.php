<?php

use App\Models\Monitor;
use App\Models\User;

test('statistics endpoint includes global enabled and disabled monitors count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create globally enabled and disabled monitors
    $enabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'is_public' => false,
    ]);
    $enabledMonitor->users()->detach($user->id);
    $enabledMonitor->users()->attach($user->id, ['is_active' => true]);

    $disabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'is_public' => false,
    ]);
    $disabledMonitor->users()->detach($user->id);
    $disabledMonitor->users()->attach($user->id, ['is_active' => true]);

    $response = $this->get('/statistic-monitor');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertArrayHasKey('globally_enabled_monitors', $data);
    $this->assertArrayHasKey('globally_disabled_monitors', $data);
    $this->assertEquals(1, $data['globally_enabled_monitors']);
    $this->assertEquals(1, $data['globally_disabled_monitors']);
});

test('private monitors endpoint supports globally enabled filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create enabled and disabled monitors
    $enabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'is_public' => false,
    ]);
    $enabledMonitor->users()->detach($user->id);
    $enabledMonitor->users()->attach($user->id, ['is_active' => true]);

    $disabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'is_public' => false,
    ]);
    $disabledMonitor->users()->detach($user->id);
    $disabledMonitor->users()->attach($user->id, ['is_active' => true]);

    // Test globally enabled filter
    $response = $this->get('/private-monitors?status_filter=globally_enabled');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertCount(1, $data['data']);
    $this->assertEquals($enabledMonitor->id, $data['data'][0]['id']);
});

test('private monitors endpoint supports globally disabled filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create enabled and disabled monitors
    $enabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'is_public' => false,
    ]);
    $enabledMonitor->users()->detach($user->id);
    $enabledMonitor->users()->attach($user->id, ['is_active' => true]);

    $disabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'is_public' => false,
    ]);
    $disabledMonitor->users()->detach($user->id);
    $disabledMonitor->users()->attach($user->id, ['is_active' => true]);

    // Test globally disabled filter
    $response = $this->get('/private-monitors?status_filter=globally_disabled');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertCount(1, $data['data']);
    $this->assertEquals($disabledMonitor->id, $data['data'][0]['id']);
});

test('public monitors endpoint supports globally enabled filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create enabled and disabled public monitors
    $enabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'is_public' => true,
    ]);

    $disabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'is_public' => true,
    ]);

    // Test globally enabled filter
    $response = $this->getJson('/public-monitors?status_filter=globally_enabled');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertCount(1, $data['data']);
    $this->assertEquals($enabledMonitor->id, $data['data'][0]['id']);
});

test('public monitors endpoint supports globally disabled filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create enabled and disabled public monitors
    $enabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'is_public' => true,
    ]);

    $disabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'is_public' => true,
    ]);

    // Test globally disabled filter
    $response = $this->getJson('/public-monitors?status_filter=globally_disabled');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertCount(1, $data['data']);
    $this->assertEquals($disabledMonitor->id, $data['data'][0]['id']);
});
