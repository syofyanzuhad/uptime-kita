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
        Schema::create('status_page_monitor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_page_id')->constrained('status_pages')->onDelete('cascade');
            $table->foreignId('monitor_id')->constrained('monitors')->onDelete('cascade');
            $table->timestamps();

            // Ensure a monitor can only be added to a status page once
            $table->unique(['status_page_id', 'monitor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_page_monitor');
    }
};
