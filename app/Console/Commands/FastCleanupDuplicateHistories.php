<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FastCleanupDuplicateHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:fast-cleanup-duplicates {--force : Actually perform the cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fast cleanup of duplicate monitor histories using SQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        if (! $force) {
            $this->warn('This is a DRY RUN. Use --force to actually perform the cleanup.');
        }

        $this->info('Starting fast cleanup of duplicate monitor histories...');

        // Count records before
        $beforeCount = DB::table('monitor_histories')->count();
        $this->info("Total records before: {$beforeCount}");

        if ($force) {
            // Step 1: Create backup table name with timestamp
            $backupTable = 'monitor_histories_backup_'.now()->format('Y_m_d_H_i_s');
            $this->info("Creating backup table: {$backupTable}");

            // Create backup table
            DB::statement("CREATE TABLE {$backupTable} AS SELECT * FROM monitor_histories");

            // Step 2: Create temporary table with unique records
            $this->info('Creating temporary table with deduplicated records...');
            DB::statement('DROP TABLE IF EXISTS temp_unique_histories');

            DB::statement('
                CREATE TEMPORARY TABLE temp_unique_histories AS 
                SELECT * FROM (
                    SELECT *,
                           ROW_NUMBER() OVER (
                               PARTITION BY monitor_id, strftime("%Y-%m-%d %H:%M", created_at) 
                               ORDER BY created_at DESC, id DESC
                           ) as rn
                    FROM monitor_histories
                ) ranked
                WHERE rn = 1
            ');

            // Step 3: Replace original table contents
            $this->info('Replacing original table with unique records...');

            // Clear original table and insert unique records
            DB::transaction(function () {
                DB::statement('DELETE FROM monitor_histories');
                DB::statement('
                    INSERT INTO monitor_histories (id, monitor_id, uptime_status, message, created_at, updated_at, response_time, status_code, checked_at)
                    SELECT id, monitor_id, uptime_status, message, created_at, updated_at, response_time, status_code, checked_at
                    FROM temp_unique_histories
                ');
            });

            // Clean up temp table
            DB::statement('DROP TABLE temp_unique_histories');

            // Count records after
            $afterCount = DB::table('monitor_histories')->count();
            $deleted = $beforeCount - $afterCount;

            $this->info('Cleanup completed!');
            $this->info("Records before: {$beforeCount}");
            $this->info("Records after: {$afterCount}");
            $this->info("Records removed: {$deleted}");
            $this->info("Backup saved as: {$backupTable}");

        } else {
            // Just count duplicates for dry run
            $duplicates = DB::select('
                SELECT COUNT(*) as duplicate_count
                FROM (
                    SELECT monitor_id, strftime("%Y-%m-%d %H:%M", created_at) as minute_key, COUNT(*) - 1 as extras
                    FROM monitor_histories 
                    GROUP BY monitor_id, strftime("%Y-%m-%d %H:%M", created_at)
                    HAVING COUNT(*) > 1
                ) dups
            ')[0];

            $wouldDelete = DB::select('
                SELECT SUM(cnt - 1) as total_duplicates
                FROM (
                    SELECT COUNT(*) as cnt
                    FROM monitor_histories 
                    GROUP BY monitor_id, strftime("%Y-%m-%d %H:%M", created_at)
                    HAVING COUNT(*) > 1
                ) grouped
            ')[0];

            $this->info('DRY RUN Results:');
            $this->info("- Total records: {$beforeCount}");
            $this->info('- Would delete: '.($wouldDelete->total_duplicates ?? 0).' duplicate records');
            $this->info('- Would keep: '.($beforeCount - ($wouldDelete->total_duplicates ?? 0)).' unique records');
        }

        return 0;
    }
}
