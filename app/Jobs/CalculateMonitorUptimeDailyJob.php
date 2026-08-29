<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateMonitorUptimeDailyJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'uptime-daily-dispatcher';
    }

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug('Starting daily uptime calculation batch job');

        try {
            // Get all monitor IDs
            $monitorIds = $this->getMonitorIds();

            if (empty($monitorIds)) {
                Log::debug('No monitors found for uptime calculation');

                return;
            }

            $lookbackDays = config('uptime-monitor.daily_lookback_days', 30);
            $startDate = now()->subDays($lookbackDays)->startOfDay();
            $endDate = now()->subDay()->endOfDay();
            $period = CarbonPeriod::create($startDate, $endDate);
            $dateStrings = [];
            foreach ($period as $date) {
                $dateStrings[] = $date->toDateString();
            }
            $yesterday = now()->subDay()->toDateString();

            // Chunk 50 keeps existingRecords query small. Dispatch individually
            // (Bus::batch needs job_batches table not present on sqlite_queue :memory: tests).
            $totalJobs = 0;
            foreach (array_chunk($monitorIds, 50) as $monitorChunk) {
                $existingRecords = MonitorUptimeDaily::whereIn('monitor_id', $monitorChunk)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get(['monitor_id', 'date'])
                    ->groupBy('monitor_id')
                    ->map(fn ($records) => $records->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all());

                foreach ($monitorChunk as $monitorId) {
                    $existingDates = $existingRecords->get($monitorId, []);

                    foreach ($dateStrings as $dateString) {
                        if ($dateString === $yesterday || ! in_array($dateString, $existingDates, true)) {
                            dispatch(new CalculateSingleMonitorUptimeJob($monitorId, $dateString))->onQueue('default');
                            $totalJobs++;
                        }
                    }
                }
            }

            if ($totalJobs === 0) {
                Log::info('No uptime calculations needed — all daily records present.');

                return;
            }

            Log::info('Dispatched uptime calculations', ['total_jobs' => $totalJobs]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch batch job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all monitor IDs for uptime calculation.
     */
    protected function getMonitorIds(): array
    {
        return Monitor::pluck('id')->toArray();
    }
}
