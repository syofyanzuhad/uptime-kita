<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateMonitorHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:cleanup-duplicates {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate monitor history records, keeping only the latest record per monitor per minute';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Cleaning up duplicate monitor histories...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be actually deleted');
        }

        // Count total records before cleanup
        $totalBefore = DB::table('monitor_histories')->count();
        $this->info("Total records before cleanup: {$totalBefore}");

        // Find all duplicate groups
        $duplicates = DB::select('
            SELECT 
                monitor_id, 
                strftime("%Y-%m-%d %H:%M", created_at) as minute_key,
                COUNT(*) as count
            FROM monitor_histories 
            GROUP BY monitor_id, strftime("%Y-%m-%d %H:%M", created_at)
            HAVING count > 1
            ORDER BY count DESC
        ');

        $this->info('Found '.count($duplicates).' minute periods with duplicate records');

        if (empty($duplicates)) {
            $this->info('No duplicates found!');

            return;
        }

        $totalDeleted = 0;
        $progressBar = $this->output->createProgressBar(count($duplicates));

        foreach ($duplicates as $duplicate) {
            // Get IDs of records to keep (latest) and delete (others)
            $records = DB::select('
                SELECT id, created_at
                FROM monitor_histories 
                WHERE monitor_id = ? 
                AND strftime("%Y-%m-%d %H:%M", created_at) = ?
                ORDER BY created_at DESC, id DESC
            ', [$duplicate->monitor_id, $duplicate->minute_key]);

            // Keep the first (latest) record, delete the rest
            $keepId = $records[0]->id;
            $deleteIds = array_slice(array_column($records, 'id'), 1);

            if (! empty($deleteIds) && ! $dryRun) {
                $deleted = DB::table('monitor_histories')
                    ->whereIn('id', $deleteIds)
                    ->delete();
                $totalDeleted += $deleted;
            } elseif (! empty($deleteIds)) {
                // Dry run - just count what would be deleted
                $totalDeleted += count($deleteIds);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("DRY RUN: Would delete {$totalDeleted} duplicate records");
        } else {
            $this->info("Deleted {$totalDeleted} duplicate records");
            $totalAfter = DB::table('monitor_histories')->count();
            $this->info("Total records after cleanup: {$totalAfter}");
        }

        // Check if there are still duplicates
        $remainingDuplicates = DB::select('
            SELECT COUNT(*) as count
            FROM (
                SELECT monitor_id, strftime("%Y-%m-%d %H:%M", created_at) as minute_key, COUNT(*) as cnt
                FROM monitor_histories 
                GROUP BY monitor_id, strftime("%Y-%m-%d %H:%M", created_at)
                HAVING cnt > 1
            ) duplicates
        ')[0]->count;

        if ($remainingDuplicates > 0) {
            $this->warn("Warning: {$remainingDuplicates} duplicate groups still remain");
        } else {
            $this->info('✅ All duplicates successfully cleaned up!');
        }
    }
}
