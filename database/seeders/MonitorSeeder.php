<?php

namespace Database\Seeders;

use App\Models\Monitor;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monitors = require database_path('seeders/monitors.php');
        $collages = require database_path('seeders/collage.php');

        // merge monitors and collages
        $monitors = array_merge($monitors, $collages);

        // add 1 minute key to each monitor
        $oneMinuteMonitors = array_map(function ($monitor) {
            $monitor['uptime_check_interval_in_minutes'] = 1;

            return $monitor;
        }, $monitors);

        // add 5 minute key to each monitor
        // $fiveMinuteMonitors = array_map(function ($monitor) {
        //     $monitor['uptime_check_interval_in_minutes'] = 5;
        //     return $monitor;
        // }, $monitors);

        $monitors = array_merge(
            $oneMinuteMonitors,
            // $fiveMinuteMonitors
        );

        foreach ($monitors as $monitor) {
            Monitor::withoutGlobalScopes()->firstOrCreate(
                [
                    'url' => $monitor['url'],
                ],
                [
                    'uptime_check_interval_in_minutes' => $monitor['uptime_check_interval_in_minutes'],
                    'uptime_check_enabled' => $monitor['uptime_check_enabled'],
                    'certificate_check_enabled' => $monitor['certificate_check_enabled'],
                    'certificate_expiration_date' => $monitor['certificate_expiration_date'] ?? null,
                    'is_public' => $monitor['is_public'] ?? true,
                ]
            );
        }
    }
}
