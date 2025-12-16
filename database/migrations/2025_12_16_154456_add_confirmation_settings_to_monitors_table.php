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
            // Confirmation settings for reducing false positives
            $table->unsignedSmallInteger('confirmation_delay_seconds')->nullable()->after('transient_failures_count');
            $table->unsignedTinyInteger('confirmation_retries')->nullable()->after('confirmation_delay_seconds');
            $table->string('sensitivity', 10)->default('medium')->after('confirmation_retries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_delay_seconds',
                'confirmation_retries',
                'sensitivity',
            ]);
        });
    }
};
