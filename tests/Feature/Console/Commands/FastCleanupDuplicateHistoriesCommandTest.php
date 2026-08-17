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

            // Create duplicate records via raw DB to bypass Eloquent hooks
            // (Hooks round created_at to 00 seconds, which prevents creating same-minute duplicates)
            DB::table('monitor_histories')->insert([
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => 'up',
                    'created_at' => $now->copy()->setSeconds(10)->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ],
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => 'down',
                    'created_at' => $now->copy()->setSeconds(30)->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ],
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

            // Create duplicate records within the same minute via raw DB to bypass Eloquent hooks
            $record1 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $now->copy()->setSeconds(10)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
            $record2 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $now->copy()->setSeconds(30)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
            $record3 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'recovery',
                'created_at' => $now->copy()->setSeconds(50)->toDateTimeString(), // Latest
                'updated_at' => $now->toDateTimeString(),
            ]);

            // Create a unique record in different minute
            $uniqueRecord = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $now->copy()->subMinutes(1)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
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
            expect($keptDuplicateRecord->id)->toBe($record3);
            expect($keptDuplicateRecord->uptime_status)->toBe('recovery');

            // Should keep the unique record
            expect(MonitorHistory::where('id', $uniqueRecord)->exists())->toBeTrue();
        });

        it('creates backup table before performing cleanup', function () {
            // Create records via raw DB to simulate legacy same-minute duplicates
            DB::table('monitor_histories')->insert([
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => 'up',
                    'created_at' => now()->copy()->setSeconds(10)->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => 'down',
                    'created_at' => now()->copy()->setSeconds(30)->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
            ]);

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

            // Create duplicates for first monitor via raw DB to bypass Eloquent hooks
            DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $now->copy()->setSeconds(10)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
            $latestRecord1 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $now->copy()->setSeconds(50)->toDateTimeString(), // Latest
                'updated_at' => $now->toDateTimeString(),
            ]);

            // Create duplicates for second monitor in same minute
            DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $monitor2->id,
                'uptime_status' => 'down',
                'created_at' => $now->copy()->setSeconds(20)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
            $latestRecord2 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $monitor2->id,
                'uptime_status' => 'recovery',
                'created_at' => $now->copy()->setSeconds(40)->toDateTimeString(), // Latest
                'updated_at' => $now->toDateTimeString(),
            ]);

            expect(MonitorHistory::count())->toBe(4);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->expectsOutput('Records before: 4')
                ->expectsOutput('Records after: 2')
                ->expectsOutput('Records removed: 2')
                ->assertSuccessful();

            // Should keep latest record from each monitor
            expect(MonitorHistory::count())->toBe(2);
            expect(MonitorHistory::where('id', $latestRecord1)->exists())->toBeTrue();
            expect(MonitorHistory::where('id', $latestRecord2)->exists())->toBeTrue();
        });

        it('uses transaction for data safety during cleanup', function () {
            // Create some test data via raw DB to bypass Eloquent hooks
            DB::table('monitor_histories')->insert([
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => 'up',
                    'created_at' => now()->copy()->setSeconds(10)->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => 'down',
                    'created_at' => now()->copy()->setSeconds(30)->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ],
            ]);

            // MonitorHistory::getDateFormatterSql() resolves the driver via DB::connection()
            DB::shouldReceive('connection')->andReturn(new class
            {
                public function getDriverName(): string
                {
                    return 'sqlite';
                }
            });

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

        it('preserves data integrity by keeping the latest record in a duplicate minute', function () {
            $now = now();

            // Create records within the same minute (different seconds) via raw DB to bypass Eloquent hooks
            $record1 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'down',
                'created_at' => $now->copy()->setSeconds(30)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            $record2 = DB::table('monitor_histories')->insertGetId([
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => $now->copy()->setSeconds(45)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            // Ensure record2 has higher ID
            expect($record2)->toBeGreaterThan($record1);

            $this->artisan('monitor:fast-cleanup-duplicates', ['--force' => true])
                ->assertSuccessful();

            // Should keep the latest record
            expect(MonitorHistory::count())->toBe(1);
            $keptRecord = MonitorHistory::first();
            expect($keptRecord->id)->toBe($record2);
            expect($keptRecord->uptime_status)->toBe('up');
        });
    });
});
