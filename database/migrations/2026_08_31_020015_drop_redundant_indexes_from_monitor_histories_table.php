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
            $table->dropIndex('monitor_histories_monitor_id_index');
            $table->dropIndex('monitor_histories_created_at_index');
            $table->dropIndex('monitor_histories_monitor_id_created_at_index');
            $table->dropIndex('monitor_histories_monitor_id_checked_at_index');
            $table->dropIndex('monitor_histories_monitor_id_uptime_status_checked_at_index');
            $table->dropIndex('monitor_histories_monitor_id_response_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->index('monitor_id', 'monitor_histories_monitor_id_index');
            $table->index('created_at', 'monitor_histories_created_at_index');
            $table->index(['monitor_id', 'created_at'], 'monitor_histories_monitor_id_created_at_index');
            $table->index(['monitor_id', 'checked_at'], 'monitor_histories_monitor_id_checked_at_index');
            $table->index(['monitor_id', 'uptime_status', 'checked_at'], 'monitor_histories_monitor_id_uptime_status_checked_at_index');
            $table->index(['monitor_id', 'response_time'], 'monitor_histories_monitor_id_response_time_index');
        });
    }
};
