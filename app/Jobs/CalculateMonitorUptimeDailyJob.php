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
        Log::info('Starting daily uptime calculation batch job');

        try {
            // Get all monitor IDs
            $monitorIds = Monitor::pluck('id')->toArray();

            if (empty($monitorIds)) {
                Log::info('No monitors found for uptime calculation');

                return;
            }

            Log::info('Creating batch jobs for monitors', [
                'total_monitors' => count($monitorIds),
                // 'monitorIds' => $monitorIds,
            ]);

            // Chunk monitors into smaller batches for better memory management
            $chunkSize = 10; // Process 10 monitors per batch to reduce database contention
            $monitorChunks = array_chunk($monitorIds, $chunkSize);
            $totalChunks = count($monitorChunks);
            $totalJobs = 0;

            Log::info('Processing monitors in chunks', [
                'total_monitors' => count($monitorIds),
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
            ]);

            foreach ($monitorChunks as $index => $monitorChunk) {
                $chunkNumber = $index + 1;

                Log::info("Processing chunk {$chunkNumber}/{$totalChunks}", [
                    'chunk_size' => count($monitorChunk),
                    'monitors_in_chunk' => $monitorChunk,
                ]);

                // Dispatch jobs individually instead of using batches
                foreach ($monitorChunk as $monitorId) {
                    $job = new \App\Jobs\CalculateSingleMonitorUptimeJob($monitorId);
                    dispatch($job);
                    $totalJobs++;
                }

                Log::info("Chunk {$chunkNumber}/{$totalChunks} dispatched successfully", [
                    'chunk_size' => count($monitorChunk),
                    'total_jobs_dispatched' => $totalJobs,
                ]);

                // Small delay between chunks to reduce database contention
                if ($chunkNumber < $totalChunks) {
                    usleep(500000); // 0.5 second delay
                }
            }

            Log::info('All chunks dispatched successfully', [
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
}
