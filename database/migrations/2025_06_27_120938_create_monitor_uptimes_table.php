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
        Schema::create('monitor_uptimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->onDelete('cascade');
            $table->enum('unit', ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY', 'ALL'])->default('DAILY')->comment('The unit of time for the uptime');
            $table->double('uptime_percentage')->default(0)->comment('The percentage of uptime for the monitor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_uptimes');
    }
};
