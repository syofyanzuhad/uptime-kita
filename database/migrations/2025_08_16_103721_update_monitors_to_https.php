<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all HTTP URLs to HTTPS
        DB::table('monitors')
            ->where('url', 'like', 'http://%')
            ->update([
                'url' => DB::raw("CONCAT('https://', SUBSTRING(url, 8))"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we can't determine
        // which URLs were originally HTTP vs HTTPS
    }
};
