<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('creates the table with the unique index when it does not exist', function () {
    Schema::dropIfExists('monitor_domain_expiration_reminders');

    $migration = require database_path('migrations/2026_08_17_191711_create_monitor_domain_expiration_reminders_table.php');
    $migration->up();

    expect(Schema::hasTable('monitor_domain_expiration_reminders'))->toBeTrue();
    expect(Schema::hasIndex('monitor_domain_expiration_reminders', 'monitor_expiration_reminders_unique'))->toBeTrue();
});

it('adds the unique index when the table already exists without it', function () {
    // Simulate the orphaned table left behind by an earlier failed migration run
    Schema::dropIfExists('monitor_domain_expiration_reminders');
    Schema::create('monitor_domain_expiration_reminders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('monitor_id')
            ->constrained('monitors')
            ->onDelete('cascade');
        $table->string('reminder_key');
        $table->timestamp('sent_at');
        $table->timestamps();
    });

    $migration = require database_path('migrations/2026_08_17_191711_create_monitor_domain_expiration_reminders_table.php');
    $migration->up();

    expect(Schema::hasTable('monitor_domain_expiration_reminders'))->toBeTrue();
    expect(Schema::hasIndex('monitor_domain_expiration_reminders', 'monitor_expiration_reminders_unique'))->toBeTrue();
});

it('is a no-op when the table already exists with the unique index', function () {
    $migration = require database_path('migrations/2026_08_17_191711_create_monitor_domain_expiration_reminders_table.php');
    $migration->up();
    $migration->up();

    expect(Schema::hasTable('monitor_domain_expiration_reminders'))->toBeTrue();
    expect(Schema::hasIndex('monitor_domain_expiration_reminders', 'monitor_expiration_reminders_unique'))->toBeTrue();
});
