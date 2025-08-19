<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove duplicate records, keeping the latest one for each monitor-minute combination
        $this->removeDuplicateHistories();

        // Add unique constraint for monitor_id + created_at (rounded to minute)
        // Since SQLite doesn't support function-based unique constraints directly,
        // we'll use a unique index on monitor_id + minute-rounded created_at
        DB::statement('CREATE UNIQUE INDEX monitor_histories_unique_minute ON monitor_histories (monitor_id, datetime(created_at, "start of minute"))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS monitor_histories_unique_minute');
    }

    /**
     * Remove duplicate history records, keeping the latest one for each monitor-minute combination
     */
    private function removeDuplicateHistories(): void
    {
        // Get all duplicate records grouped by monitor_id and minute
        $duplicates = DB::select('
            SELECT monitor_id, datetime(created_at, "start of minute") as minute_start, COUNT(*) as count
            FROM monitor_histories 
            GROUP BY monitor_id, datetime(created_at, "start of minute")
            HAVING count > 1
        ');

        echo 'Found '.count($duplicates)." minute periods with duplicate records\n";

        foreach ($duplicates as $duplicate) {
            // Keep the latest record for each monitor-minute combination
            $keepRecord = DB::selectOne('
                SELECT id 
                FROM monitor_histories 
                WHERE monitor_id = ? 
                AND datetime(created_at, "start of minute") = ?
                ORDER BY created_at DESC, id DESC 
                LIMIT 1
            ', [$duplicate->monitor_id, $duplicate->minute_start]);

            if (! $keepRecord) {
                echo "Warning: No record found for monitor {$duplicate->monitor_id} at {$duplicate->minute_start}\n";

                continue;
            }

            // Delete all other records for this monitor-minute combination
            $deletedCount = DB::delete('
                DELETE FROM monitor_histories 
                WHERE monitor_id = ? 
                AND datetime(created_at, "start of minute") = ?
                AND id != ?
            ', [$duplicate->monitor_id, $duplicate->minute_start, $keepRecord->id]);

            if ($deletedCount > 0) {
                echo "Removed {$deletedCount} duplicate records for monitor {$duplicate->monitor_id} at {$duplicate->minute_start}\n";
            }
        }
    }
};
