<?php

namespace App\Console\Commands;

use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Commands\CheckUptime as SpatieCheckUptime;
use Spatie\UptimeMonitor\MonitorCollection;
use Throwable;

class MonitorCheckUptime extends SpatieCheckUptime
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $url = $this->option('url');
            $force = $this->option('force');
            $totalCount = 0;

            $query = Monitor::query();

            // When forcing, we check all enabled monitors
            if (! $force) {
                // Otherwise we only check ones that are enabled (shouldCheckUptime filter applied later)
                $query->where('uptime_check_enabled', true);
            }

            $query->chunk(200, function ($monitors) use ($url, $force, &$totalCount) {
                // Filter by URL if provided
                if ($url) {
                    $urls = explode(',', $url);
                    $monitors = $monitors->filter(function ($monitor) use ($urls) {
                        return in_array((string) $monitor->url, $urls);
                    });
                }

                // Filter by those due for a check (unless forced)
                $monitorsToPing = $force
                    ? $monitors
                    : $monitors->filter->shouldCheckUptime();

                if ($monitorsToPing->isEmpty()) {
                    return;
                }

                $totalCount += $monitorsToPing->count();
                $this->comment('Checking uptime of '.$monitorsToPing->count().' monitors in this chunk...');

                $monitorCollection = MonitorCollection::make($monitorsToPing);
                $monitorCollection->checkUptime();
            });

            $this->info("All done! Checked {$totalCount} monitors in total.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('monitor:check-uptime failed', [
                'exception' => $e,
            ]);

            $this->error('Uptime check failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
