<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            // Check if the unique constraint already exists
            // if (Schema::hasColumn('monitor_uptime_dailies', 'monitor_id') && Schema::hasColumn('monitor_uptime_dailies', 'date')) {
            //     return; // Unique constraint already exists, no need to add it again
            // }
            $table->unique(['monitor_id', 'date'], 'monitor_uptime_daily_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            // Check if the unique constraint exists before dropping it
            // if (!Schema::hasColumn('monitor_uptime_dailies', 'monitor_id') || !Schema::hasColumn('monitor_uptime_dailies', 'date')) {
            //     return; // Columns do not exist, no need to drop the constraint
            // }
            // Drop the unique constraint on monitor_id and date
            $table->dropUnique('monitor_uptime_daily_unique');
        });
    }
};
