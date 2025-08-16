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
        // Add response time fields to monitor_histories
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->integer('response_time')->nullable()->after('uptime_status');
            $table->integer('status_code')->nullable()->after('response_time');
            $table->timestamp('checked_at')->nullable()->after('status_code');
            $table->index(['monitor_id', 'response_time']);
        });

        // Add response time statistics to monitor_uptime_dailies
        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            $table->float('avg_response_time')->nullable()->after('uptime_percentage');
            $table->float('min_response_time')->nullable()->after('avg_response_time');
            $table->float('max_response_time')->nullable()->after('min_response_time');
            $table->integer('total_checks')->default(0)->after('max_response_time');
            $table->integer('failed_checks')->default(0)->after('total_checks');
        });

        // Add metadata fields to monitors
        Schema::table('monitors', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('url');
            $table->text('description')->nullable()->after('display_name');
            $table->integer('expected_status_code')->default(200)->after('uptime_check_enabled');
            $table->integer('max_response_time')->nullable()->after('expected_status_code');
            $table->json('check_locations')->nullable()->after('max_response_time');
            $table->json('notification_settings')->nullable()->after('check_locations');
        });

        // Create monitor_incidents table
        Schema::create('monitor_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['down', 'degraded', 'recovered']);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->text('reason')->nullable();
            $table->integer('response_time')->nullable();
            $table->integer('status_code')->nullable();
            $table->timestamps();

            $table->index(['monitor_id', 'started_at']);
            $table->index(['monitor_id', 'type']);
        });

        // Create monitor_performance_hourly table
        Schema::create('monitor_performance_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->timestamp('hour');
            $table->float('avg_response_time')->nullable();
            $table->float('p95_response_time')->nullable();
            $table->float('p99_response_time')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->unique(['monitor_id', 'hour']);
            $table->index(['monitor_id', 'hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove fields from monitor_histories
        Schema::table('monitor_histories', function (Blueprint $table) {
            $table->dropIndex(['monitor_id', 'response_time']);
            $table->dropColumn(['response_time', 'status_code', 'checked_at']);
        });

        // Remove fields from monitor_uptime_dailies
        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            $table->dropColumn([
                'avg_response_time',
                'min_response_time',
                'max_response_time',
                'total_checks',
                'failed_checks',
            ]);
        });

        // Remove fields from monitors
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'description',
                'expected_status_code',
                'max_response_time',
                'check_locations',
                'notification_settings',
            ]);
        });

        // Drop new tables
        Schema::dropIfExists('monitor_performance_hourly');
        Schema::dropIfExists('monitor_incidents');
    }
};
