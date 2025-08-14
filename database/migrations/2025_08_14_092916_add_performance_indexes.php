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
        // Add indexes for monitor_histories table
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->index('monitor_id');
            $table->index('created_at');
            $table->index(['monitor_id', 'created_at']);
        });

        // Add indexes for monitors table
        Schema::table('monitors', function (Blueprint $table) {
            $table->index('uptime_check_enabled');
            $table->index('is_public');
            $table->index('uptime_last_check_date');
            $table->index('uptime_status');
            $table->index(['uptime_check_enabled', 'is_public']);
        });

        // Add indexes for user_monitor table
        Schema::table('user_monitor', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('created_at');
            $table->index(['user_id', 'is_active']);
            $table->index(['monitor_id', 'is_active']);
        });

        // Add indexes for status_page_monitor table
        Schema::table('status_page_monitor', function (Blueprint $table) {
            $table->index('order');
            $table->index(['status_page_id', 'order']);
        });

        // Add indexes for sessions table (performance for auth queries)
        Schema::table('sessions', function (Blueprint $table) {
            $table->index(['user_id', 'last_activity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes for monitor_histories table
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->dropIndex(['monitor_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['monitor_id', 'created_at']);
        });

        // Remove indexes for monitors table
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropIndex(['uptime_check_enabled']);
            $table->dropIndex(['is_public']);
            $table->dropIndex(['uptime_last_check_date']);
            $table->dropIndex(['uptime_status']);
            $table->dropIndex(['uptime_check_enabled', 'is_public']);
        });

        // Remove indexes for user_monitor table
        Schema::table('user_monitor', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropIndex(['monitor_id', 'is_active']);
        });

        // Remove indexes for status_page_monitor table
        Schema::table('status_page_monitor', function (Blueprint $table) {
            $table->dropIndex(['order']);
            $table->dropIndex(['status_page_id', 'order']);
        });

        // Remove indexes for sessions table
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'last_activity']);
        });
    }
};
