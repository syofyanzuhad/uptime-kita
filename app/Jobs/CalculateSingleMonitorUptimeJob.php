<?php

namespace App\Jobs;

<<<<<<< HEAD
use App\Models\Monitor;
=======
use Exception;
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
use Illuminate\Bus\Queueable;
<<<<<<< HEAD
=======
use Illuminate\Bus\Batchable;
use Illuminate\Support\Carbon;
use App\Models\MonitorUptimeDaily;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
<<<<<<< HEAD
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
=======
use Illuminate\Contracts\Queue\ShouldBeUnique;
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568

<<<<<<< HEAD
class CalculateMonitorUptimeDailyJob implements ShouldQueue
=======
class CalculateSingleMonitorUptimeJob implements ShouldQueue, ShouldBeUnique
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

<<<<<<< HEAD
=======
    public int $monitorId;
    public string $date;

    // Job configuration
    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [30, 60, 120]; // Exponential backoff in seconds
    public $uniqueFor = 3600; // 1 hour uniqueness

>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "uptime_calc_{$this->monitorId}_{$this->date}";
    }

    /**
     * Create a new job instance.
     */
<<<<<<< HEAD
    public function __construct() {}
=======
    public function __construct(int $monitorId, ?string $date = null)
    {
        $this->monitorId = $monitorId;
        $this->date = $date ?? Carbon::today()->toDateString();

        // Set the queue for this job
        $this->onQueue('uptime-calculations');
    }
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568

    /**
     * Execute the job.
     */
    public function handle(): void
    {
<<<<<<< HEAD
        Log::info('Starting daily uptime calculation batch job');
=======
        Log::info('Starting uptime calculation', [
            'monitor_id' => $this->monitorId,
            'date' => $this->date,
            'attempt' => $this->attempts()
        ]);
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568

<<<<<<< HEAD
        try {
            // Get all monitor IDs
            $monitorIds = Monitor::pluck('id')->toArray();
=======
        try {
            // Validate monitor exists first
            if (!$this->monitorExists()) {
                Log::warning('Monitor not found, skipping calculation', [
                    'monitor_id' => $this->monitorId
                ]);
                return;
            }
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568

<<<<<<< HEAD
            if (empty($monitorIds)) {
                Log::info('No monitors found for uptime calculation');
                return;
            }
=======
            // Validate date format
            if (!$this->isValidDate()) {
                throw new Exception("Invalid date format: {$this->date}");
            }

            $this->calculateAndStoreUptime();

            Log::info('Uptime calculation completed successfully', [
                'monitor_id' => $this->monitorId,
                'date' => $this->date
            ]);

        } catch (Exception $e) {
            Log::error('Uptime calculation failed', [
                'monitor_id' => $this->monitorId,
                'date' => $this->date,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568

<<<<<<< HEAD
            Log::info('Creating batch jobs for monitors', [
                'total_monitors' => count($monitorIds)
            ]);
=======
    /**
     * Check if monitor exists
     */
    private function monitorExists(): bool
    {
        return DB::table('monitors')
            ->where('id', $this->monitorId)
            ->exists();
    }
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568

<<<<<<< HEAD
            // Chunk monitors into smaller batches for better memory management
            $chunkSize = 10; // Process 10 monitors per batch to reduce database contention
            $monitorChunks = array_chunk($monitorIds, $chunkSize);
            $totalChunks = count($monitorChunks);
            $totalJobs = 0;

            Log::info('Processing monitors in chunks', [
                'total_monitors' => count($monitorIds),
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks
=======
    /**
     * Validate date format
     */
    private function isValidDate(): bool
    {
        try {
            Carbon::createFromFormat('Y-m-d', $this->date);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Calculate and store uptime data
     */
    private function calculateAndStoreUptime(): void
    {
        DB::transaction(function () {
            // Use a single database query to get both total and up counts
            $result = DB::table('monitor_histories')
                ->selectRaw('
                    COUNT(*) as total_checks,
                    SUM(CASE WHEN uptime_status = ? THEN 1 ELSE 0 END) as up_checks
                ')
                ->where('monitor_id', $this->monitorId)
                ->whereDate('created_at', $this->date)
                ->setBindings(['up'])
                ->first();

            // Handle case where no checks found
            if (!$result || $result->total_checks === 0) {
                Log::info('No monitor history found for date', [
                    'monitor_id' => $this->monitorId,
                    'date' => $this->date
                ]);
                $this->updateUptimeRecord(0);
                return;
            }

            $uptimePercentage = ($result->up_checks / $result->total_checks) * 100;

            Log::debug('Uptime calculation details', [
                'monitor_id' => $this->monitorId,
                'date' => $this->date,
                'total_checks' => $result->total_checks,
                'up_checks' => $result->up_checks,
                'uptime_percentage' => $uptimePercentage
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
            ]);
<<<<<<< HEAD

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
=======

            $this->updateUptimeRecord($uptimePercentage);
        });
    }

    /**
     * Update or create the uptime record with proper SQLite concurrency handling
     */
    private function updateUptimeRecord(float $uptimePercentage): void
    {
        // Ensure date is in correct format (Y-m-d, not datetime)
        $dateOnly = Carbon::parse($this->date)->toDateString();
        $roundedPercentage = round($uptimePercentage, 2);

        try {
            // First, try to update existing record
            $updated = MonitorUptimeDaily::where('monitor_id', $this->monitorId)
                ->where('date', $dateOnly)
                ->update([
                    'uptime_percentage' => $roundedPercentage,
                    'updated_at' => now()
                ]);

            if ($updated) {
                Log::debug('Uptime record updated (existing)', [
                    'monitor_id' => $this->monitorId,
                    'date' => $dateOnly,
                    'uptime_percentage' => $roundedPercentage
                ]);
                return;
            }

            // If no record was updated, try to create new one
            MonitorUptimeDaily::create([
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'uptime_percentage' => $roundedPercentage
            ]);

            Log::debug('Uptime record created (new)', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'uptime_percentage' => $roundedPercentage
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle UNIQUE constraint violation specifically
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry')) {

                Log::warning('Concurrent job detected during create, retrying update', [
                    'monitor_id' => $this->monitorId,
                    'date' => $dateOnly,
                    'attempt' => $this->attempts()
                ]);

                // Another job created the record between our check and create
                // Try update again
                $retryUpdated = MonitorUptimeDaily::where('monitor_id', $this->monitorId)
                    ->where('date', $dateOnly)
                    ->update([
                        'uptime_percentage' => $roundedPercentage,
                        'updated_at' => now()
                    ]);

                if ($retryUpdated) {
                    Log::info('Successfully handled concurrent job via retry update', [
                        'monitor_id' => $this->monitorId,
                        'date' => $dateOnly
                    ]);
                    return;
                }

                // If still no update, the record might not exist (edge case)
                // This shouldn't happen, but let's log it and not fail the job
                Log::warning('Record disappeared after constraint violation - possible race condition', [
                    'monitor_id' => $this->monitorId,
                    'date' => $dateOnly
                ]);

                // Don't throw exception for this edge case, just log and continue
                return;
            }

            // For other database errors, log and re-throw
            Log::error('Database error in uptime record operation', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings()
            ]);
            throw $e;

        } catch (Exception $e) {
            Log::error('Unexpected error in uptime record operation', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'error' => $e->getMessage()
            ]);
            throw $e;
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
        }
    }
<<<<<<< HEAD
=======

    /**
     * Handle job retry logic
     */
    public function retryUntil()
    {
        return now()->addMinutes(30);
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateSingleMonitorUptimeJob permanently failed', [
            'monitor_id' => $this->monitorId,
            'date' => $this->date,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optional: Send notification to administrators
        // event(new UptimeCalculationFailed($this->monitorId, $this->date, $exception));
    }
>>>>>>> 6c6e784644940435633883e83a6640a8cd242568
}
