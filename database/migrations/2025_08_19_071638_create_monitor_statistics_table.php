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
        Schema::create('monitor_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            
            // Time periods
            $table->decimal('uptime_1h', 5, 2)->default(100.00);
            $table->decimal('uptime_24h', 5, 2)->default(100.00);
            $table->decimal('uptime_7d', 5, 2)->default(100.00);
            $table->decimal('uptime_30d', 5, 2)->default(100.00);
            $table->decimal('uptime_90d', 5, 2)->default(100.00);
            
            // Response time statistics (24h)
            $table->integer('avg_response_time_24h')->nullable();
            $table->integer('min_response_time_24h')->nullable();
            $table->integer('max_response_time_24h')->nullable();
            
            // Incident counts
            $table->integer('incidents_24h')->default(0);
            $table->integer('incidents_7d')->default(0);
            $table->integer('incidents_30d')->default(0);
            
            // Total checks count for different periods
            $table->integer('total_checks_24h')->default(0);
            $table->integer('total_checks_7d')->default(0);
            $table->integer('total_checks_30d')->default(0);
            
            // Recent history cache (JSON for last 100 minutes)
            $table->json('recent_history_100m')->nullable();
            
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->unique('monitor_id');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_statistics');
    }
};
