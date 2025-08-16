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
        Schema::table('status_pages', function (Blueprint $table) {
            $table->string('custom_domain')->nullable()->unique()->after('path');
            $table->boolean('custom_domain_verified')->default(false)->after('custom_domain');
            $table->string('custom_domain_verification_token')->nullable()->after('custom_domain_verified');
            $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain_verification_token');
            $table->boolean('force_https')->default(true)->after('custom_domain_verified_at');

            // Add index for faster lookups
            $table->index('custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_pages', function (Blueprint $table) {
            $table->dropIndex(['custom_domain']);
            $table->dropColumn([
                'custom_domain',
                'custom_domain_verified',
                'custom_domain_verification_token',
                'custom_domain_verified_at',
                'force_https',
            ]);
        });
    }
};
