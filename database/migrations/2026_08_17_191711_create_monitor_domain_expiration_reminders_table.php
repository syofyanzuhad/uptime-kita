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
        if (! Schema::hasTable('monitor_domain_expiration_reminders')) {
            Schema::create('monitor_domain_expiration_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('monitor_id')
                    ->constrained('monitors')
                    ->onDelete('cascade');
                $table->string('reminder_key');
                $table->timestamp('sent_at');
                $table->timestamps();

                $table->unique(['monitor_id', 'reminder_key'], 'monitor_expiration_reminders_unique');
            });
        }

        // A previous failed run may have left the table behind without its unique
        // index, so add it idempotently rather than failing the deploy.
        if (! Schema::hasIndex('monitor_domain_expiration_reminders', 'monitor_expiration_reminders_unique')) {
            Schema::table('monitor_domain_expiration_reminders', function (Blueprint $table) {
                $table->unique(['monitor_id', 'reminder_key'], 'monitor_expiration_reminders_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_domain_expiration_reminders');
    }
};
