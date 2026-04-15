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
        Schema::table('monitor_histories', function (Blueprint $table) {
            // Covering index for statistics calculation (uptime status and response time)
            $table->index(['monitor_id', 'uptime_status', 'created_at'], 'monitor_histories_stats_index');
            $table->index(['monitor_id', 'response_time', 'created_at'], 'monitor_histories_perf_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->dropIndex('monitor_histories_stats_index');
            $table->dropIndex('monitor_histories_perf_index');
        });
    }
};
