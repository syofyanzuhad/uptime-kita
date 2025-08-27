<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('StatisticMonitorController', function () {
    beforeEach(function () {
        // Create monitors with different statuses
        $this->upMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $this->downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $this->recoveryMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
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
            'total' => 3,
            'up' => 1,
            'down' => 1,
        ]);
    });

    it('counts only public and enabled monitors', function () {
        // Create private monitor
        $privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $privateMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        // Create disabled monitor
        $disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $disabledMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total' => 3, // Only public and enabled monitors
            'up' => 1,
            'down' => 1,
        ]);
    });

    it('counts up monitors correctly', function () {
        // Create additional up monitors
        $additionalUpMonitors = Monitor::factory()->count(3)->create([
            'is_public' => true,
            'is_enabled' => true,
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
            'total' => 6,
            'up' => 4,
            'down' => 1,
        ]);
    });

    it('counts down monitors correctly', function () {
        // Create additional down monitors
        $additionalDownMonitors = Monitor::factory()->count(2)->create([
            'is_public' => true,
            'is_enabled' => true,
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
            'total' => 5,
            'up' => 1,
            'down' => 3,
        ]);
    });

    it('treats recovery status as up', function () {
        // Create monitor with recovery status
        $recoveryMonitor2 = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $recoveryMonitor2->id,
            'uptime_status' => 'recovery',
            'created_at' => now(),
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total' => 4,
            'up' => 2, // Both up and recovery statuses count as up
            'down' => 1,
        ]);
    });

    it('handles monitors without history', function () {
        // Create monitor without history
        Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
        ]);

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total' => 4,
            'up' => 1,
            'down' => 1,
        ]);
    });

    it('uses latest history for each monitor', function () {
        // Create monitor with multiple history entries
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
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
            'total' => 4,
            'up' => 2, // Should count the latest status (up)
            'down' => 1,
        ]);
    });

    it('returns zero counts when no monitors exist', function () {
        // Delete all monitors
        Monitor::query()->delete();

        $response = get('/statistic-monitor');

        $response->assertOk();
        $response->assertJson([
            'total' => 0,
            'up' => 0,
            'down' => 0,
        ]);
    });

    it('handles mixed monitor statuses', function () {
        // Create monitors with various statuses
        $statuses = ['up', 'down', 'recovery', 'maintenance'];

        foreach ($statuses as $status) {
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'is_enabled' => true,
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
        expect($data['total'])->toBe(7); // 3 from beforeEach + 4 new
        expect($data['up'] + $data['down'])->toBeLessThanOrEqual($data['total']);
    });
});
