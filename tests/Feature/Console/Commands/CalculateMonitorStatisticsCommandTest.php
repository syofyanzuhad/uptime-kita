<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorStatistic;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(now());
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('CalculateMonitorStatistics', function () {
    describe('handle', function () {
        it('processes all public monitors when no specific monitor is provided', function () {
            // Create public monitors
            Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);
            Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

            // Create private monitor (should be ignored)
            Monitor::factory()->create(['is_public' => false, 'uptime_check_enabled' => true]);

            // Create disabled public monitor (should be ignored)
            Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => false]);

            $this->artisan('monitor:calculate-statistics')
                ->expectsOutput('Calculating statistics for 2 monitor(s)...')
                ->expectsOutput('Monitor statistics calculated successfully!')
                ->assertSuccessful();

            // Should have created statistics for 2 public monitors
            expect(MonitorStatistic::count())->toBe(2);
        });

        it('processes specific monitor when monitor ID is provided', function () {
            $publicMonitor = Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);
            $otherPublicMonitor = Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

            $this->artisan('monitor:calculate-statistics', ['monitor' => $publicMonitor->id])
                ->expectsOutput('Calculating statistics for 1 monitor(s)...')
                ->expectsOutput('Monitor statistics calculated successfully!')
                ->assertSuccessful();

            // Should have created statistics for only the specified monitor
            expect(MonitorStatistic::count())->toBe(1);
            expect(MonitorStatistic::where('monitor_id', $publicMonitor->id)->exists())->toBeTrue();
            expect(MonitorStatistic::where('monitor_id', $otherPublicMonitor->id)->exists())->toBeFalse();
        });

        it('ignores private monitors even when specified by ID', function () {
            $privateMonitor = Monitor::factory()->create(['is_public' => false, 'uptime_check_enabled' => true]);

            $this->artisan('monitor:calculate-statistics', ['monitor' => $privateMonitor->id])
                ->expectsOutput('No public monitors found.')
                ->assertSuccessful();

            expect(MonitorStatistic::count())->toBe(0);
        });

        it('shows warning when no public monitors are found', function () {
            // Create only private monitors
            Monitor::factory()->create(['is_public' => false, 'uptime_check_enabled' => true]);

            $this->artisan('monitor:calculate-statistics')
                ->expectsOutput('No public monitors found.')
                ->assertSuccessful();

            expect(MonitorStatistic::count())->toBe(0);
        });
    });

    describe('calculateStatistics', function () {
        it('calculates and stores comprehensive statistics', function () {
            $monitor = Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

            // Create historical data
            $now = now();

            // Create uptime histories for different time periods
            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => 'up',
                'response_time' => 100,
                'created_at' => $now->copy()->subMinutes(30),
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => 'down',
                'response_time' => null,
                'created_at' => $now->copy()->subHours(2),
            ]);

            MonitorHistory::factory()->create([
                'monitor_id' => $monitor->id,
                'uptime_status' => 'up',
                'response_time' => 200,
                'created_at' => $now->copy()->subHours(12),
            ]);

            $this->artisan('monitor:calculate-statistics', ['monitor' => $monitor->id])
                ->assertSuccessful();

            $statistic = MonitorStatistic::where('monitor_id', $monitor->id)->first();

            expect($statistic)->not->toBeNull();
            expect($statistic->monitor_id)->toBe($monitor->id);
            expect($statistic->calculated_at)->not->toBeNull();

            // Should have uptime percentages
            expect($statistic->uptime_1h)->toBeFloat();
            expect($statistic->uptime_24h)->toBeFloat();
            expect($statistic->uptime_7d)->toBeFloat();
            expect($statistic->uptime_30d)->toBeFloat();
            expect($statistic->uptime_90d)->toBeFloat();

            // Should have response time stats (may be null if no response times)
            expect($statistic->avg_response_time_24h)->toBeInt();
            expect($statistic->min_response_time_24h)->toBeInt();
            expect($statistic->max_response_time_24h)->toBeInt();

            // Should have incident counts
            expect($statistic->incidents_24h)->toBeInt();
            expect($statistic->incidents_7d)->toBeInt();
            expect($statistic->incidents_30d)->toBeInt();

            // Should have total check counts
            expect($statistic->total_checks_24h)->toBeInt();
            expect($statistic->total_checks_7d)->toBeInt();
            expect($statistic->total_checks_30d)->toBeInt();

            // Should have recent history
            expect($statistic->recent_history_100m)->toBeArray();
        });

        it('updates existing statistics record', function () {
            $monitor = Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

            // Create existing statistic
            $existingStatistic = MonitorStatistic::create([
                'monitor_id' => $monitor->id,
                'uptime_1h' => 50.0,
                'uptime_24h' => 50.0,
                'uptime_7d' => 50.0,
                'uptime_30d' => 50.0,
                'uptime_90d' => 50.0,
                'calculated_at' => now()->subHour(),
            ]);

            $this->artisan('monitor:calculate-statistics', ['monitor' => $monitor->id])
                ->assertSuccessful();

            // Should still have only one statistic record
            expect(MonitorStatistic::where('monitor_id', $monitor->id)->count())->toBe(1);

            // Should have updated the calculated_at timestamp
            $updatedStatistic = MonitorStatistic::where('monitor_id', $monitor->id)->first();
            expect($updatedStatistic->calculated_at->isAfter($existingStatistic->calculated_at))->toBeTrue();
        });

        it('handles monitors with no history gracefully', function () {
            $monitor = Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

            $this->artisan('monitor:calculate-statistics', ['monitor' => $monitor->id])
                ->assertSuccessful();

            $statistic = MonitorStatistic::where('monitor_id', $monitor->id)->first();

            expect($statistic)->not->toBeNull();

            // Should default to 100% uptime when no history exists
            expect($statistic->uptime_1h)->toBe(100.0);
            expect($statistic->uptime_24h)->toBe(100.0);
            expect($statistic->uptime_7d)->toBe(100.0);
            expect($statistic->uptime_30d)->toBe(100.0);
            expect($statistic->uptime_90d)->toBe(100.0);

            // Response time stats should be null when no history
            expect($statistic->avg_response_time_24h)->toBeNull();
            expect($statistic->min_response_time_24h)->toBeNull();
            expect($statistic->max_response_time_24h)->toBeNull();

            // Counts should be zero
            expect($statistic->incidents_24h)->toBe(0);
            expect($statistic->total_checks_24h)->toBe(0);

            // Recent history should be empty array
            expect($statistic->recent_history_100m)->toBe([]);
        });
    });
});
