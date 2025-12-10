<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(now());
    $this->monitor = Monitor::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('FastCleanupDuplicateHistories', function () {
    describe('handle', function () {
        it('performs dry run by default without force flag', function () {
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

            $this->artisan('monitor:fast-cleanup-duplicates')
                ->expectsOutput('This is a DRY RUN. Use --force to actually perform the cleanup.')
                ->expectsOutput('Starting fast cleanup of duplicate monitor histories...')
                ->expectsOutput('Total records before: 2')
                ->expectsOutput('DRY RUN Results:')
                ->expectsOutput('- Total records: 2')
                ->expectsOutput('- Would delete: 1 duplicate records')
                ->expectsOutput('- Would keep: 1 unique records')
                ->assertSuccessful();

            // Records should remain unchanged in dry run
            expect(MonitorHistory::count())->toBe(2);
        });

        it('performs actual cleanup when force flag is used', function () {
            $now = now();

            // Create duplicate records within the same minute
            $record1 = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
                'uptime_status' => 'down',
            ]);
            $record2 = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(30),
                'uptime_status' => 'up',
            ]);
            $record3 = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(50), // Latest
                'uptime_status' => 'recovery',
            ]);

            // Create a unique record in different minute
            $uniqueRecord = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->subMinutes(1),
                'uptime_status' => 'up',
            ]);

            expect(MonitorHistory::count())->toBe(4);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->expectsOutput('Starting fast cleanup of duplicate monitor histories...')
                ->expectsOutput('Total records before: 4')
                ->expectsOutputToContain('Creating backup table: monitor_histories_backup_')
                ->expectsOutput('Creating temporary table with deduplicated records...')
                ->expectsOutput('Replacing original table with unique records...')
                ->expectsOutput('Cleanup completed!')
                ->expectsOutput('Records before: 4')
                ->expectsOutput('Records after: 2')
                ->expectsOutput('Records removed: 2')
                ->expectsOutputToContain('Backup saved as: monitor_histories_backup_')
                ->assertSuccessful();

            // Should have removed duplicates, keeping only latest from each minute
            expect(MonitorHistory::count())->toBe(2);

            // Should keep the latest record from the duplicate group (record3)
            $keptDuplicateRecord = MonitorHistory::where('created_at', 'like', $now->format('Y-m-d H:i:%'))->first();
            expect($keptDuplicateRecord->id)->toBe($record3->id);
            expect($keptDuplicateRecord->uptime_status)->toBe('recovery');

            // Should keep the unique record
            expect(MonitorHistory::where('id', $uniqueRecord->id)->exists())->toBeTrue();
        });

        it('creates backup table before performing cleanup', function () {
            MonitorHistory::factory()->create(['monitor_id' => $this->monitor->id]);
            MonitorHistory::factory()->create(['monitor_id' => $this->monitor->id]);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->assertSuccessful();

            // Check that backup table was created
            $backupTableName = 'monitor_histories_backup_'.now()->format('Y_m_d_H_i_s');

            // Query to check if any backup table with today's date exists
            $backupTables = DB::select("
                SELECT name FROM sqlite_master 
                WHERE type='table' AND name LIKE 'monitor_histories_backup_%'
            ");

            expect(count($backupTables))->toBeGreaterThan(0);

            // Verify backup table contains original data
            $backupTable = $backupTables[0]->name;
            $backupCount = DB::table($backupTable)->count();
            expect($backupCount)->toBe(2);
        });

        it('handles records with no duplicates gracefully', function () {
            // Create unique records (no duplicates)
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(1),
            ]);
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes(2),
            ]);

            $this->artisan('monitor:fast-cleanup-duplicates')
                ->expectsOutput('- Would delete: 0 duplicate records')
                ->expectsOutput('- Would keep: 2 unique records')
                ->assertSuccessful();

            // Force run should also work
            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->expectsOutput('Records removed: 0')
                ->assertSuccessful();

            expect(MonitorHistory::count())->toBe(2);
        });

        it('handles multiple monitors with duplicates correctly', function () {
            $monitor2 = Monitor::factory()->create();
            $now = now();

            // Create duplicates for first monitor
            MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(10),
                'uptime_status' => 'down',
            ]);
            $latestRecord1 = MonitorHistory::factory()->create([
                'monitor_id' => $this->monitor->id,
                'created_at' => $now->copy()->setSeconds(50), // Latest
                'uptime_status' => 'up',
            ]);

            // Create duplicates for second monitor in same minute
            MonitorHistory::factory()->create([
                'monitor_id' => $monitor2->id,
                'created_at' => $now->copy()->setSeconds(20),
                'uptime_status' => 'down',
            ]);
            $latestRecord2 = MonitorHistory::factory()->create([
                'monitor_id' => $monitor2->id,
                'created_at' => $now->copy()->setSeconds(40), // Latest
                'uptime_status' => 'recovery',
            ]);

            expect(MonitorHistory::count())->toBe(4);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->expectsOutput('Records before: 4')
                ->expectsOutput('Records after: 2')
                ->expectsOutput('Records removed: 2')
                ->assertSuccessful();

            // Should keep latest record from each monitor
            expect(MonitorHistory::count())->toBe(2);
            expect(MonitorHistory::where('id', $latestRecord1->id)->exists())->toBeTrue();
            expect(MonitorHistory::where('id', $latestRecord2->id)->exists())->toBeTrue();
        });

        it('uses transaction for data safety during cleanup', function () {
            // Create some test data
            MonitorHistory::factory()->create(['monitor_id' => $this->monitor->id]);
            MonitorHistory::factory()->create(['monitor_id' => $this->monitor->id]);

            // Mock DB transaction to ensure it's called
            DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
                return $callback();
            });

            // Allow other DB methods
            DB::shouldReceive('table')->andReturnSelf();
            DB::shouldReceive('count')->andReturn(2, 1);
            DB::shouldReceive('statement')->andReturn(true);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->assertSuccessful();
        });

        it('preserves data integrity by keeping latest record with highest ID when timestamps are identical', function () {
            $now = now();

            // Create records with identical timestamps but different IDs
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

            // Ensure record2 has higher ID
            expect($record2->id)->toBeGreaterThan($record1->id);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->assertSuccessful();

            // Should keep the record with higher ID
            expect(MonitorHistory::count())->toBe(1);
            $keptRecord = MonitorHistory::first();
            expect($keptRecord->id)->toBe($record2->id);
            expect($keptRecord->uptime_status)->toBe('up');
        });
    });
});
