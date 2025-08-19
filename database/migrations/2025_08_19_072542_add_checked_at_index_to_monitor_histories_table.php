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
            // Add composite index for monitor_id + checked_at for efficient range queries
            $table->index(['monitor_id', 'checked_at']);

            // Add composite index for monitor_id + uptime_status + checked_at for filtered queries
            $table->index(['monitor_id', 'uptime_status', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->dropIndex(['monitor_id', 'checked_at']);
            $table->dropIndex(['monitor_id', 'uptime_status', 'checked_at']);
        });
    }
};
