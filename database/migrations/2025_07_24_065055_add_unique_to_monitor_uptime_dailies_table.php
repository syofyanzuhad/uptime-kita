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
            // drop index on date if it exists
            // if (Schema::hasIndex('monitor_uptime_dailies', 'monitor_uptime_dailies_date_index')) {
            $table->dropIndex('monitor_uptime_dailies_date_index');
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
            // Recreate the index on date
            // if (!Schema::hasIndex('monitor_uptime_dailies', 'monitor_uptime_dailies_date_index')) {
            $table->index('date', 'monitor_uptime_dailies_date_index');
            // }
            // Drop the unique constraint on monitor_id and date
            $table->dropUnique('monitor_uptime_daily_unique');
        });
    }
};
