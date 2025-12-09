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
        Schema::create('telemetry_pings', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id', 64)->unique()->index();
            $table->string('app_version')->nullable();
            $table->string('php_version')->nullable();
            $table->string('laravel_version')->nullable();
            $table->unsignedInteger('monitors_total')->default(0);
            $table->unsignedInteger('monitors_public')->default(0);
            $table->unsignedInteger('users_total')->default(0);
            $table->unsignedInteger('status_pages_total')->default(0);
            $table->string('os_family')->nullable();
            $table->string('os_type')->nullable();
            $table->string('database_driver')->nullable();
            $table->string('queue_driver')->nullable();
            $table->string('cache_driver')->nullable();
            $table->date('install_date')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->unsignedInteger('ping_count')->default(1);
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetry_pings');
    }
};
