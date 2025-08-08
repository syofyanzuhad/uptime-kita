<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::connection('sqlite')->dropIfExists('telescope_entries');
        Schema::connection('sqlite')->dropIfExists('telescope_entries_tags');
        Schema::connection('sqlite')->dropIfExists('telescope_monitoring');

        // Drop Queue tables from the 'sqlite' connection
        Schema::connection('sqlite')->dropIfExists('jobs');
        Schema::connection('sqlite')->dropIfExists('failed_jobs');
        Schema::connection('sqlite')->dropIfExists('job_batches');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
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


