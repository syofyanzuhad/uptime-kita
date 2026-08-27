<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hapus data duplikat — keep MAX(id) per (monitor_id, date)
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            // ponytail: JOIN delete avoids MySQL 1093 (can't specify target table in FROM)
            DB::statement('
                DELETE m1 FROM `monitor_uptime_dailies` m1
                INNER JOIN `monitor_uptime_dailies` m2
                    ON m1.`monitor_id` = m2.`monitor_id`
                    AND m1.`date` = m2.`date`
                    AND m1.`id` < m2.`id`
            ');
        } else {
            DB::statement('
                DELETE FROM monitor_uptime_dailies
                WHERE id NOT IN (
                    SELECT MAX(id)
                    FROM monitor_uptime_dailies
                    GROUP BY monitor_id, date
                )
            ');
        }

        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            // 2. Hapus index lama jika ada (opsional, tergantung struktur Anda)
            // Pastikan nama indexnya benar. Anda bisa cek di database client.
            // Jika tidak yakin, Anda bisa comment baris ini.
            // $table->dropIndex('monitor_uptime_dailies_date_index');

            // 3. Sekarang aman untuk menambahkan unique constraint
            $table->unique(['monitor_id', 'date'], 'monitor_uptime_daily_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_uptime_dailies', function (Blueprint $table) {
            // Urutan di 'down' adalah kebalikan dari 'up'
            $table->dropUnique('monitor_uptime_daily_unique');
            // $table->index('date', 'monitor_uptime_dailies_date_index');
        });
    }
};
