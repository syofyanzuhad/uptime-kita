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
        Schema::table('status_page_monitor', function (Blueprint $table) {
            // check if the 'order' column already exists
            if (Schema::hasColumn('status_page_monitor', 'order')) {
                return; // Column already exists, no need to add it again
            }
            // Add the 'order' column to the status_page_monitor table
            $table->integer('order')->default(0)->after('monitor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_page_monitor', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
