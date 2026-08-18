<?php

namespace App\Jobs;

use App\Models\Monitor;
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

            foreach ($monitorChunks as $index => $monitorChunk) {
                $chunkNumber = $index + 1;

                Log::debug("Processing chunk {$chunkNumber}/{$totalChunks}", [
                    'chunk_size' => count($monitorChunk),
                ]);

                // Calculate for yesterday to ensure we have a full day's data
                $yesterday = now()->subDay()->toDateString();

                // Dispatch jobs individually instead of using batches
                foreach ($monitorChunk as $monitorId) {
                    $job = new CalculateSingleMonitorUptimeJob($monitorId, $yesterday);
                    dispatch($job);
                    $totalJobs++;
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
