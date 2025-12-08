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
        Schema::table('monitors', function (Blueprint $table) {
            // JSON field for storing maintenance windows configuration
            // Format: [{"type": "recurring", "day_of_week": 0, "start_time": "02:00", "end_time": "04:00", "timezone": "Asia/Jakarta"}]
            // or: [{"type": "one_time", "start": "2025-12-15T02:00:00+07:00", "end": "2025-12-15T04:00:00+07:00"}]
            $table->json('maintenance_windows')->nullable()->after('notification_settings');

            // Quick access fields for active maintenance window
            $table->timestamp('maintenance_starts_at')->nullable()->after('maintenance_windows');
            $table->timestamp('maintenance_ends_at')->nullable()->after('maintenance_starts_at');

            // Boolean flag for quick checking if monitor is in maintenance
            $table->boolean('is_in_maintenance')->default(false)->after('maintenance_ends_at');

            // Track transient failures for statistics
            $table->unsignedInteger('transient_failures_count')->default(0)->after('is_in_maintenance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_windows',
                'maintenance_starts_at',
                'maintenance_ends_at',
                'is_in_maintenance',
                'transient_failures_count',
            ]);
        });
    }
};
