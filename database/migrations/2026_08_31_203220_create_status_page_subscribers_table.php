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
        Schema::create('status_page_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_page_id')->constrained('status_pages')->cascadeOnDelete();
            $table->string('email');
            $table->string('verification_token', 64)->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();

            $table->unique(['status_page_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_page_subscribers');
    }
};
