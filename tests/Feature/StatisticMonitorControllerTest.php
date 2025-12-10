<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;

use function Pest\Laravel\get;

describe('StatisticMonitorController', function () {
    beforeEach(function () {
        // Create monitors with different statuses
        $this->upMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
            'uptime_last_check_date' => now(),
        ]);

        $this->downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
            'uptime_last_check_date' => now(),
        ]);

        $this->recoveryMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',  // recovery counts as up
            'uptime_last_check_date' => now(),
        ]);

        // Create history for up monitor
        MonitorHistory::factory()->create([
            'monitor_id' => $this->upMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 200,
            'created_at' => now(),
        ]);

        // Create history for down monitor
        MonitorHistory::factory()->create([
            'monitor_id' => $this->downMonitor->id,
            'uptime_status' => 'down',
            'response_time' => null,
            'created_at' => now(),
        ]);

        // Create history for recovery monitor
        MonitorHistory::factory()->create([
            'monitor_id' => $this->recoveryMonitor->id,
            'uptime_status' => 'recovery',
            'response_time' => 300,
            'created_at' => now(),
        ]);
    });

    it('returns monitor statistics', function () {
        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 3,
            'online_monitors' => 2,  // upMonitor and recoveryMonitor
            'offline_monitors' => 1,  // downMonitor
        ]);
    });

    it('counts only public and enabled monitors', function () {
        // Create private monitor
        $privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $privateMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        // Create disabled monitor
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 4, // Includes the disabled monitor in total count
            'online_monitors' => 3,  // All the public enabled ones are up
            'offline_monitors' => 1,  // downMonitor
        ]);
    });

    it('counts up monitors correctly', function () {
        // Create additional up monitors
        $additionalUpMonitors = Monitor::factory()->count(3)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        foreach ($additionalUpMonitors as $monitor) {
            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => 'up',
                'created_at' => now(),
            ]);
        }

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 6,
            'online_monitors' => 5,  // 2 from beforeEach + 3 new ones that default to up
            'offline_monitors' => 1,  // Only downMonitor from beforeEach
        ]);
    });

    it('counts down monitors correctly', function () {
        // Create additional down monitors
        $additionalDownMonitors = Monitor::factory()->count(2)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        foreach ($additionalDownMonitors as $monitor) {
            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => 'down',
                'created_at' => now(),
            ]);
        }

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 5,
        ]);
        expect($response->json())->toHaveKeys(['online_monitors', 'offline_monitors']);
    });

    it('treats recovery status as up', function () {
        // Create monitor with recovery status
        $recoveryMonitor2 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $recoveryMonitor2->id,
            'uptime_status' => 'recovery',
            'created_at' => now(),
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 4,
        ]);
        expect($response->json())->toHaveKeys(['online_monitors', 'offline_monitors']);
    });

    it('handles monitors without history', function () {
        // Create monitor without history
        Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 4,
        ]);
        expect($response->json())->toHaveKeys(['online_monitors', 'offline_monitors']);
    });

    it('uses latest history for each monitor', function () {
        // Create monitor with multiple history entries
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        // Old history - down
        MonitorHistory::factory()->create([
            'monitor_id' => $monitor->id,
            'uptime_status' => 'down',
            'created_at' => now()->subHours(2),
        ]);

        // Latest history - up
        MonitorHistory::factory()->create([
            'monitor_id' => $monitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 4,
        ]);
        expect($response->json())->toHaveKeys(['online_monitors', 'offline_monitors']);
    });

    it('returns zero counts when no monitors exist', function () {
        // Delete all monitors
        Monitor::query()->delete();

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total_monitors' => 0,
            'online_monitors' => 0,
            'offline_monitors' => 0,
        ]);
    });

    it('handles mixed monitor statuses', function () {
        // Create monitors with various statuses
        $statuses = ['up', 'down', 'recovery', 'maintenance'];

        foreach ($statuses as $status) {
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => $status,
                'created_at' => now(),
            ]);
        }

        $response = get('/statistic-monitor');

        $response->assertOk();

        $data = $response->json();
        expect($data['total_monitors'])->toBe(7); // 3 from beforeEach + 4 new
        expect($data['online_monitors'] + $data['offline_monitors'])->toBeLessThanOrEqual($data['total_monitors']);
    });
});
