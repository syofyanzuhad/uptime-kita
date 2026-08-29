<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitorBatchJob;
use App\Models\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
            DB::disableQueryLog();

            $url = $this->option('url');
            $force = $this->option('force');

            $query = Monitor::query();

            // When forcing, we check all enabled monitors
            if (! $force) {
                // Otherwise we only check ones that are enabled (shouldCheckUptime filter applied later)
                $query->where('uptime_check_enabled', true);
            }

            $monitors = $query->get();

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
                $this->info('No monitors due for uptime check.');

                return self::SUCCESS;
            }

            $totalCount = $monitorsToPing->count();
            $queueThreshold = (int) config('uptime-monitor.uptime_check.queue_threshold', 500);
            $batchSize = (int) config('uptime-monitor.uptime_check.batch_size', 100);

            // Adaptive: If total monitors exceed threshold, dispatch in batches to managed queue
            if ($totalCount > $queueThreshold) {
                $batches = $monitorsToPing->pluck('id')->chunk($batchSize);
                $batchCount = $batches->count();

                $this->info("Total monitors due ({$totalCount}) exceeds threshold ({$queueThreshold}). Dispatching {$batchCount} batch jobs to queue...");

                foreach ($batches as $chunkIds) {
                    CheckMonitorBatchJob::dispatch($chunkIds->values()->all());
                }

                return self::SUCCESS;
            }

            // Otherwise, process directly in this process using lightweight chunks
            $monitorsToPing->chunk(100)->each(function ($chunk) {
                $this->comment('Checking uptime of '.$chunk->count().' monitors in this chunk...');

                $monitorCollection = MonitorCollection::make($chunk);
                $monitorCollection->checkUptime();

                unset($monitorCollection);
                gc_collect_cycles();
            });

            $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            $this->info("All done! Checked {$totalCount} monitors in total. (Peak memory: {$peakMemory} MB)");

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
