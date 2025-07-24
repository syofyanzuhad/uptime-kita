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
            // Ensure that each monitor can only have one record per day
            $table->unique(['monitor_id', 'date'], 'monitor_uptime_daily_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            // Drop the unique constraint on monitor_id and date
            $table->dropUnique('monitor_uptime_daily_unique');
        });
    }
};
