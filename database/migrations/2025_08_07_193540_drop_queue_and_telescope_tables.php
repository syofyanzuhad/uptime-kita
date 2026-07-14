<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_monitoring');

        Schema::dropIfExists('jobs');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-run the original migrations for each database.
        // This will recreate all the tables we just dropped.
        // Note: This requires 'force' if you run it in a production environment.
        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);

        Artisan::call('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);
    }
};
