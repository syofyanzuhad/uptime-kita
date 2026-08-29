<?php

namespace App\Jobs;

use App\Models\Monitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\MonitorCollection;
use Throwable;

class CheckMonitorBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $monitorIds
     */
    public function __construct(
        public array $monitorIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->monitorIds)) {
            return;
        }

        try {
            DB::disableQueryLog();

            $monitors = Monitor::withoutGlobalScopes()
                ->whereIn('id', $this->monitorIds)
                ->where('uptime_check_enabled', true)
                ->get();

            $monitorsToPing = $monitors->filter->shouldCheckUptime();

            if ($monitorsToPing->isEmpty()) {
                return;
            }

            $monitorCollection = MonitorCollection::make($monitorsToPing);
            $monitorCollection->checkUptime();

            unset($monitors, $monitorsToPing, $monitorCollection);
            gc_collect_cycles();
        } catch (Throwable $e) {
            Log::warning('CheckMonitorBatchJob encountered an error: '.$e->getMessage(), [
                'monitor_ids' => $this->monitorIds,
                'exception' => $e,
            ]);
        }
    }
}
