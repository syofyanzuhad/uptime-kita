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
        Schema::table('monitors', function (Blueprint $table) {
            $table->boolean('domain_expiration_check_enabled')->default(false)
                ->after('certificate_check_failure_reason');
            $table->timestamp('domain_expiration_date')->nullable()
                ->after('domain_expiration_check_enabled');
            $table->string('domain_expiration_lookup_error')->nullable()
                ->after('domain_expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'domain_expiration_check_enabled',
                'domain_expiration_date',
                'domain_expiration_lookup_error',
            ]);
        });
    }
};
