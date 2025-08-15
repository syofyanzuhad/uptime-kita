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
        // 1. Hapus data duplikat terlebih dahulu
        // Query ini akan menyimpan record dengan 'id' tertinggi (paling baru)
        // untuk setiap kombinasi 'monitor_id' dan 'date', lalu menghapus sisanya.
        DB::table('monitor_uptime_dailies')
            ->whereIn('id', function ($query) {
                $query->select('id')
                    ->from('monitor_uptime_dailies')
                    ->whereIn('id', function ($subQuery) {
                        $subQuery->select(DB::raw('MIN(id)'))
                            ->from('monitor_uptime_dailies')
                            ->groupBy('monitor_id', 'date')
                            ->having(DB::raw('COUNT(*)'), '>', 1);
                    });
            })->delete();

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
