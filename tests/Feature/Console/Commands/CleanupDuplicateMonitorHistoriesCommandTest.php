<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(now());
    $this->monitor = Monitor::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('CleanupDuplicateMonitorHistories', function () {
    describe('handle', function () {
        it('shows message when no duplicates exist', function () {
            // Create unique monitor histories (no duplicates)
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(1),
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(2),
            ]);

            $this->artisan('monitor:cleanup-duplicates')
                ->expectsOutput('Found 0 minute periods with duplicate records')
                ->expectsOutput('No duplicates found!')
                ->assertSuccessful();
        });

        it('identifies and removes duplicate records', function () {
            $now = now();

            // Create duplicate records within the same minute
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
                'uptime_status' => 'up',
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30), // Same minute, later
                'uptime_status' => 'down',
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(50), // Same minute, latest
                'uptime_status' => 'up',
            ]);

            // Create a unique record in a different minute
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->subMinutes(1),
            ]);

            expect(MonitorHistory::count())->toBe(4);

            $this->artisan('monitor:cleanup-duplicates')
                ->expectsOutput('Total records before cleanup: 4')
                ->expectsOutput('Found 1 minute periods with duplicate records')
                ->expectsOutput('Deleted 2 duplicate records')
                ->expectsOutput('Total records after cleanup: 2')
                ->expectsOutput('✅ All duplicates successfully cleaned up!')
                ->assertSuccessful();

            // Should keep only 2 records (1 latest from duplicate group + 1 unique)
            expect(MonitorHistory::count())->toBe(2);

            // The kept record should be the latest one (with seconds=50)
            $keptRecord = MonitorHistory::where('created_at', 'like', $now->format('Y-m-d H:i:%'))->first();
            expect($keptRecord->created_at->second)->toBe(50);
            expect($keptRecord->uptime_status)->toBe('up');
        });

        it('handles dry-run mode correctly', function () {
            $now = now();

            // Create duplicate records
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30),
            ]);

            expect(MonitorHistory::count())->toBe(2);

            $this->artisan('monitor:cleanup-duplicates', ['--dry-run' => true])
                ->expectsOutput('DRY RUN MODE - No records will be actually deleted')
                ->expectsOutput('Total records before cleanup: 2')
                ->expectsOutput('Found 1 minute periods with duplicate records')
                ->expectsOutput('DRY RUN: Would delete 1 duplicate records')
                ->assertSuccessful();

            // Records should still exist in dry-run mode
            expect(MonitorHistory::count())->toBe(2);
        });

        it('processes multiple monitors with duplicates', function () {
            $monitor2 = Monitor::factory()->create();
            $now = now();

            // Create duplicates for first monitor
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30),
            ]);

            // Create duplicates for second monitor in different minute
            MonitorHistory::factory()->create([
                'monitor_id' => $monitor2->id,
                'created_at' => $now->copy()->subMinutes(1)->setSeconds(15),
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $monitor2->id,
                'created_at' => $now->copy()->subMinutes(1)->setSeconds(45),
            ]);

            expect(MonitorHistory::count())->toBe(4);

            $this->artisan('monitor:cleanup-duplicates')
                ->expectsOutput('Total records before cleanup: 4')
                ->expectsOutput('Found 2 minute periods with duplicate records')
                ->expectsOutput('Deleted 2 duplicate records')
                ->expectsOutput('Total records after cleanup: 2')
                ->assertSuccessful();

            // Should keep 1 record per monitor
            expect(MonitorHistory::count())->toBe(2);
            expect(MonitorHistory::where('monitor_id', $this->monitor->id)->count())->toBe(1);
            expect(MonitorHistory::where('monitor_id', $monitor2->id)->count())->toBe(1);
        });

        it('keeps the latest record by created_at and id when multiple records exist', function () {
            $now = now();

            // Create records with same created_at but different IDs
            $record1 = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30),
                'uptime_status' => 'down',
            ]);

            $record2 = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30), // Same timestamp
                'uptime_status' => 'up',
            ]);

            // The second record should have a higher ID
            expect($record2->id)->toBeGreaterThan($record1->id);

            $this->artisan('monitor:cleanup-duplicates')
                ->assertSuccessful();

            // Should keep only the record with higher ID
            expect(MonitorHistory::count())->toBe(1);
            $keptRecord = MonitorHistory::first();
            expect($keptRecord->id)->toBe($record2->id);
            expect($keptRecord->uptime_status)->toBe('up');
        });

        it('warns when duplicates still remain after cleanup', function () {
            // This test simulates a scenario where cleanup might not work perfectly
            // We'll create a situation and then mock the remaining duplicates check
            $now = now();

            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30),
            ]);

            // Mock the duplicate check query to return that duplicates remain
            DB::shouldReceive('select')
                ->andReturnUsing(function ($query, $bindings = []) {
                    // For the initial duplicate finding query
                    if (str_contains($query, 'HAVING count > 1')) {
                        return [(object) ['monitor_id' => $this->monitor->id, 'minute_key' => now()->format('Y-m-d H:i'), 'count' => 2]];
                    }
                    // For the detailed record query
                    if (str_contains($query, 'ORDER BY created_at DESC')) {
                        return [
                            (object) ['id' => 1, 'created_at' => now()->format('Y-m-d H:i:30')],
                            (object) ['id' => 2, 'created_at' => now()->format('Y-m-d H:i:10')],
                        ];
                    }
                    // For the remaining duplicates check - simulate that 1 duplicate remains
                    if (str_contains($query, 'HAVING cnt > 1')) {
                        return [(object) ['count' => 1]];
                    }

                    return [];
                });

            DB::shouldReceive('table')->andReturnSelf();
            DB::shouldReceive('count')->andReturn(2, 2); // Before and after counts
            DB::shouldReceive('whereIn')->andReturnSelf();
            DB::shouldReceive('delete')->andReturn(1);

            $this->artisan('monitor:cleanup-duplicates')
                ->expectsOutput('Warning: 1 duplicate groups still remain')
                ->assertSuccessful();
        });
    });
});
