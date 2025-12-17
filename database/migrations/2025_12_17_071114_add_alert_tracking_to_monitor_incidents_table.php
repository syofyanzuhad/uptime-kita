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
        Schema::table('monitor_incidents', function (Blueprint $table) {
            $table->boolean('down_alert_sent')->default(false)->after('status_code');
            $table->unsignedInteger('last_alert_at_failure_count')->nullable()->after('down_alert_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_incidents', function (Blueprint $table) {
            $table->dropColumn(['down_alert_sent', 'last_alert_at_failure_count']);
        });
    }
};
