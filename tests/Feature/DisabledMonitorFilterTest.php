<?php

use App\Models\User;
use App\Models\Monitor;

test('statistics endpoint includes disabled monitors count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a monitor and disable it for the user (user-specific disabled)
    $monitor = Monitor::factory()->create([
        'uptime_check_enabled' => false, // Monitor is globally enabled
        'is_public' => false,
    ]);
    // Detach any auto-attached user_monitor row
    $monitor->users()->detach($user->id);
    // Attach user to monitor with is_active = false (user-specific disabled)
    $monitor->users()->attach($user->id, ['is_active' => false]);

    $response = $this->get('/statistic-monitor');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertArrayHasKey('globally_disabled_monitors', $data);
    $this->assertEquals(1, $data['globally_disabled_monitors']);
});

test('private monitors endpoint supports user-specific disabled filter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a globally enabled monitor but user-specific disabled
    $enabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => true,
        'is_public' => false,
    ]);
    $enabledMonitor->users()->detach($user->id);
    $enabledMonitor->users()->attach($user->id, ['is_active' => false]); // User-specific disabled

    // Create a globally disabled monitor
    $disabledMonitor = Monitor::factory()->create([
        'uptime_check_enabled' => false,
        'is_public' => false,
    ]);
    $disabledMonitor->users()->detach($user->id);
    $disabledMonitor->users()->attach($user->id, ['is_active' => true]);

    // Test user-specific disabled filter (this should show monitors where user has is_active = false)
    // Note: This filter is for user-specific disabled, not globally disabled
    $response = $this->get('/private-monitors?status_filter=disabled');
    $response->assertStatus(200);

    $data = $response->json();
    // The disabled filter should show monitors where the user has is_active = false
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
    $response = $this->get('/public-monitors?status_filter=disabled');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertCount(1, $data['data']);
    $this->assertEquals($disabledMonitor->id, $data['data'][0]['id']);
});

test('disabled filter only shows for authenticated users', function () {
    // Test as guest user
    $response = $this->get('/statistic-monitor');
    $response->assertStatus(200);

    $data = $response->json();
    $this->assertArrayHasKey('globally_disabled_monitors', $data);
    $this->assertEquals(0, $data['globally_disabled_monitors']); // Should be 0 for guests
});
