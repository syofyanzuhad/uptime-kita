<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX monitor_histories_unique_minute ON monitor_histories (monitor_id, datetime(created_at, "start of minute"))');
        } else {
            DB::statement('CREATE UNIQUE INDEX monitor_histories_unique_minute ON monitor_histories (monitor_id, (DATE_FORMAT(created_at, \'%Y-%m-%d %H:%i:00\')))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_histories', function ($table) {
            $table->dropIndex('monitor_histories_unique_minute');
        });
    }

    /**
     * Get the database-agnostic SQL expression for start of minute.
     */
    private function getMinuteExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? 'datetime(created_at, "start of minute")'
            : "DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:00')";
    }

    /**
     * Remove duplicate history records, keeping the latest one for each monitor-minute combination
     */
    private function removeDuplicateHistories(): void
    {
        $minuteExpr = $this->getMinuteExpression();

        // Get all duplicate records grouped by monitor_id and minute
        $duplicates = DB::select("
            SELECT monitor_id, {$minuteExpr} as minute_start, COUNT(*) as count
            FROM monitor_histories 
            GROUP BY monitor_id, {$minuteExpr}
            HAVING count > 1
        ");

        echo 'Found '.count($duplicates)." minute periods with duplicate records\n";

        foreach ($duplicates as $duplicate) {
            // Keep the latest record for each monitor-minute combination
            $keepRecord = DB::selectOne("
                SELECT id 
                FROM monitor_histories 
                WHERE monitor_id = ? 
                AND {$minuteExpr} = ?
                ORDER BY created_at DESC, id DESC 
                LIMIT 1
            ", [$duplicate->monitor_id, $duplicate->minute_start]);

            if (! $keepRecord) {
                echo "Warning: No record found for monitor {$duplicate->monitor_id} at {$duplicate->minute_start}\n";

                continue;
            }

            // Delete all other records for this monitor-minute combination
            $deletedCount = DB::delete("
                DELETE FROM monitor_histories 
                WHERE monitor_id = ? 
                AND {$minuteExpr} = ?
                AND id != ?
            ", [$duplicate->monitor_id, $duplicate->minute_start, $keepRecord->id]);

            if ($deletedCount > 0) {
                echo "Removed {$deletedCount} duplicate records for monitor {$duplicate->monitor_id} at {$duplicate->minute_start}\n";
            }
        }
    }
};
