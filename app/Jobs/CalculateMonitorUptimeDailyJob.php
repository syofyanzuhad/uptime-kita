<?php

namespace App\Jobs;

use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
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
                'total_monitors' => count($monitorIds)
            ]);

            // Chunk monitors into smaller batches for better memory management
            $chunkSize = 10; // Process 10 monitors per batch to reduce database contention
            $monitorChunks = array_chunk($monitorIds, $chunkSize);
            $totalChunks = count($monitorChunks);
            $totalJobs = 0;

            Log::info('Processing monitors in chunks', [
                'total_monitors' => count($monitorIds),
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks
            ]);

            foreach ($monitorChunks as $index => $monitorChunk) {
                $chunkNumber = $index + 1;

                // Create jobs for current chunk
                $jobs = collect($monitorChunk)->map(function ($monitorId) {
                    return new \App\Jobs\CalculateSingleMonitorUptimeJob($monitorId);
                })->toArray();

                // Dispatch chunk as a batch
                $batch = Bus::batch($jobs)
                    ->name("Calculate Monitor Uptime Daily - Chunk {$chunkNumber}/{$totalChunks}")
                    ->allowFailures()
                    ->onQueue('uptime-calculations')
                    ->dispatch();

                $totalJobs += count($jobs);

                Log::info("Chunk {$chunkNumber}/{$totalChunks} dispatched successfully", [
                    'batch_id' => $batch->id,
                    'chunk_size' => count($jobs),
                    'monitors_in_chunk' => $monitorChunk
                ]);

                // Small delay between chunks to reduce database contention
                if ($chunkNumber < $totalChunks) {
                    usleep(500000); // 0.5 second delay
                }
            }

            Log::info('All chunks dispatched successfully', [
                'total_chunks' => $totalChunks,
                'total_jobs' => $totalJobs
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch batch job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
