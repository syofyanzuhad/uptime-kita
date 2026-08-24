<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateMonitorUptimeDailyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

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

            Log::debug('Creating batch jobs for monitors', [
                'total_monitors' => count($monitorIds),
            ]);

            // Chunk monitors into smaller batches for better memory management
            $chunkSize = 10; // Process 10 monitors per batch to reduce database contention
            $monitorChunks = array_chunk($monitorIds, $chunkSize);
            $totalChunks = count($monitorChunks);
            $totalJobs = 0;

            Log::debug('Processing monitors in chunks', [
                'total_monitors' => count($monitorIds),
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
            ]);

            $lookbackDays = config('uptime-monitor.daily_lookback_days', 30);
            $startDate = now()->subDays($lookbackDays)->startOfDay();
            $endDate = now()->subDay()->endOfDay();
            $period = CarbonPeriod::create($startDate, $endDate);
            $dateStrings = [];
            foreach ($period as $date) {
                $dateStrings[] = $date->toDateString();
            }

            foreach ($monitorChunks as $index => $monitorChunk) {
                $chunkNumber = $index + 1;

                Log::debug("Processing chunk {$chunkNumber}/{$totalChunks}", [
                    'chunk_size' => count($monitorChunk),
                ]);

                // Fetch all existing daily records for the monitors in this chunk within the lookback window
                $existingRecords = MonitorUptimeDaily::whereIn('monitor_id', $monitorChunk)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get(['monitor_id', 'date'])
                    ->groupBy('monitor_id')
                    ->map(fn ($records) => $records->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all());

                $yesterday = now()->subDay()->toDateString();

                foreach ($monitorChunk as $monitorId) {
                    $existingDates = $existingRecords->get($monitorId, []);

                    foreach ($dateStrings as $dateString) {
                        // Always calculate yesterday (to ensure final day's numbers are calculated/updated)
                        // or calculate any missing previous day within the lookback window
                        if ($dateString === $yesterday || ! in_array($dateString, $existingDates, true)) {
                            $job = new CalculateSingleMonitorUptimeJob($monitorId, $dateString);
                            dispatch($job);
                            $totalJobs++;
                        }
                    }
                }

                Log::debug("Chunk {$chunkNumber}/{$totalChunks} dispatched", [
                    'total_jobs_dispatched' => $totalJobs,
                ]);

                // Small delay between chunks to reduce database contention
                if ($chunkNumber < $totalChunks) {
                    usleep(500000); // 0.5 second delay
                }
            }

            Log::info('All uptime calculation chunks dispatched', [
                'total_chunks' => $totalChunks,
                'total_jobs' => $totalJobs,
            ]);

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
