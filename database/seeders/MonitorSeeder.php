<?php

namespace Database\Seeders;

use App\Models\Monitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monitors = require database_path('seeders/monitors/monitors.php');
        $collages = require database_path('seeders/monitors/collage.php');
        $goverments = require database_path('seeders/monitors/goverments.php');

        // merge monitors and collages
        $allMonitors = array_merge($monitors, $collages, $goverments);

        // create an array from monitor strings
        // and convert them to an array of associative arrays
        // with url, name, status, and uptime_check_interval_in_minutes
        // map each monitor to an associative array
        $allMonitors = array_map(function ($monitor) {
            // If it's a string, treat as URL
            return [
                'url' => $monitor,
                'uptime_check_enabled' => 1,
                'certificate_check_enabled' => 1,
                'is_public' => 1,
                'uptime_check_interval_in_minutes' => 1,
            ];
        }, $allMonitors);

        // add 5 minute key to each monitor
        // $fiveMinuteMonitors = array_map(function ($monitor) {
        //     $monitor['uptime_check_interval_in_minutes'] = 5;
        //     return $monitor;
        // }, $monitors);

        // $monitors = array_merge(
        //     $oneMinuteMonitors,
        //     // $fiveMinuteMonitors
        // );

        // upsert
        DB::table('monitors')->upsert(
            $allMonitors,
            [
                'url',
                'uptime_check_enabled',
            ], // unique by url
            [
                'certificate_check_enabled',
                'is_public',
                'uptime_check_interval_in_minutes',
            ]
        );
    }
}
