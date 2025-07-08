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
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'telegram', 'slack', 'email', etc.
            $table->string('destination'); // chat_id, email, webhook, etc.
            $table->boolean('is_enabled')->default(true);
            $table->json('metadata')->nullable(); // optional, for additional info
            $table->timestamps();

            // Add indexes for performance
            $table->index(['user_id', 'is_enabled']);
            $table->index(['type', 'is_enabled']);

            // Prevent duplicate channels
            $table->unique(['user_id', 'type', 'destination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
