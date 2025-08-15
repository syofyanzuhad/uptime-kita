<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateSingleMonitorUptimeJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $monitorId;

    public string $date;

    // Job configuration
    public $tries = 3;

    public $timeout = 300; // 5 minutes

    public $backoff = [30, 60, 120]; // Exponential backoff in seconds

    public $uniqueFor = 3600; // 1 hour uniqueness

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "uptime_calc_{$this->monitorId}_{$this->date}";
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public function uniqueFor(): int
    {
        return 3600; // 1 hour
    }

    /**
     * Create a new job instance.
     */
    public function __construct(int $monitorId, ?string $date = null)
    {
        $this->monitorId = $monitorId;
        $this->date = $date ?? Carbon::today()->toDateString();

        // Set the queue for this job
        $this->onQueue('uptime-calculations');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting uptime calculation', [
            'monitor_id' => $this->monitorId,
            'date' => $this->date,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Validate monitor exists first
            if (! $this->monitorExists()) {
                Log::warning('Monitor not found, skipping calculation', [
                    'monitor_id' => $this->monitorId,
                ]);

                return;
            }

            // Validate date format
            if (! $this->isValidDate()) {
                throw new Exception("Invalid date format: {$this->date}");
            }

            $this->calculateAndStoreUptime();

            Log::info('Uptime calculation completed successfully', [
                'monitor_id' => $this->monitorId,
                'date' => $this->date,
            ]);

        } catch (Exception $e) {
            Log::error('Uptime calculation failed', [
                'monitor_id' => $this->monitorId,
                'date' => $this->date,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Check if monitor exists
     */
    private function monitorExists(): bool
    {
        return DB::table('monitors')
            ->where('id', $this->monitorId)
            ->exists();
    }

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
        // Use a single database query to get both total and up counts
        $result = DB::table('monitor_histories')
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as up_checks
            ')
            ->where('monitor_id', $this->monitorId)
            ->whereDate('created_at', $this->date)
            ->first();

        Log::info('Monitor history result', [
            'result' => $result,
            'monitor_id' => $this->monitorId,
            'date' => $this->date,
        ]);

        // Handle case where no checks found
        if (! $result || $result->total_checks === 0) {
            Log::info('No monitor history found for date', [
                'monitor_id' => $this->monitorId,
                'date' => $this->date,
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
            'uptime_percentage' => $uptimePercentage,
        ]);

        $this->updateUptimeRecord($uptimePercentage);
    }

    /**
     * Update or create the uptime record with proper SQLite concurrency handling
     */
    private function updateUptimeRecord(float $uptimePercentage): void
    {
        // Ensure date is in correct format (Y-m-d, not datetime)
        $dateOnly = Carbon::parse($this->date)->toDateString();
        $roundedPercentage = round($uptimePercentage, 2);

        $maxRetries = 5;
        $retryDelay = 100; // milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Use upsert approach with retry mechanism for SQLite concurrency
                $result = DB::table('monitor_uptime_dailies')
                    ->where('monitor_id', $this->monitorId)
                    ->where('date', $dateOnly)
                    ->lockForUpdate()
                    ->first();

                Log::info('Monitor uptime record', [
                    'result' => $result,
                    'monitor_id' => $this->monitorId,
                    'date' => $dateOnly,
                ]);

                if ($result) {
                    // Record exists, update it
                    $updated = DB::table('monitor_uptime_dailies')
                        ->where('monitor_id', $this->monitorId)
                        ->where('date', $dateOnly)
                        ->update([
                            'uptime_percentage' => $roundedPercentage,
                            'updated_at' => now(),
                        ]);

                    if ($updated) {
                        Log::debug('Uptime record updated (existing)', [
                            'monitor_id' => $this->monitorId,
                            'date' => $dateOnly,
                            'uptime_percentage' => $roundedPercentage,
                            'attempt' => $attempt,
                        ]);

                        return;
                    }
                } else {
                    // Record doesn't exist, create it
                    DB::table('monitor_uptime_dailies')->insert([
                        'monitor_id' => $this->monitorId,
                        'date' => $dateOnly,
                        'uptime_percentage' => $roundedPercentage,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::debug('Uptime record created (new)', [
                        'monitor_id' => $this->monitorId,
                        'date' => $dateOnly,
                        'uptime_percentage' => $roundedPercentage,
                        'attempt' => $attempt,
                    ]);

                    return;
                }

            } catch (\Illuminate\Database\QueryException $e) {
                // Handle database locking and constraint violations
                if (str_contains($e->getMessage(), 'database is locked') ||
                    str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                    str_contains($e->getMessage(), 'Duplicate entry')) {

                    Log::warning('Database concurrency issue detected, retrying', [
                        'monitor_id' => $this->monitorId,
                        'date' => $dateOnly,
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                    ]);

                    if ($attempt < $maxRetries) {
                        // Wait before retry with exponential backoff
                        usleep($retryDelay * $attempt * 1000); // Convert to microseconds

                        continue;
                    } else {
                        // Final attempt failed, try one more time with simple upsert
                        $this->fallbackUpsert($dateOnly, $roundedPercentage);

                        return;
                    }
                }

                // For other database errors, log and re-throw
                Log::error('Database error in uptime record operation', [
                    'monitor_id' => $this->monitorId,
                    'date' => $dateOnly,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                throw $e;
            } catch (Exception $e) {
                Log::error('Unexpected error in uptime record operation', [
                    'monitor_id' => $this->monitorId,
                    'date' => $dateOnly,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                throw $e;
            }
        }
    }

    /**
     * Fallback upsert method using raw SQL for maximum compatibility
     */
    private function fallbackUpsert(string $dateOnly, float $roundedPercentage): void
    {
        try {
            // Use raw SQL for upsert operation
            $sql = `INSERT INTO monitor_uptime_dailies (monitor_id, date, uptime_percentage, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?)
                    ON CONFLICT(monitor_id, date)
                    DO UPDATE SET
                        uptime_percentage = excluded.uptime_percentage,
                        updated_at = excluded.updated_at`;

            DB::statement($sql, [
                $this->monitorId,
                $dateOnly,
                $roundedPercentage,
                now(),
                now(),
            ]);

            Log::info('Uptime record updated via fallback upsert', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'uptime_percentage' => $roundedPercentage,
            ]);

        } catch (Exception $e) {
            Log::error('Fallback upsert also failed', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'error' => $e->getMessage(),
            ]);
            // Don't throw here, just log the failure
        }
    }

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
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optional: Send notification to administrators
        // event(new UptimeCalculationFailed($this->monitorId, $this->date, $exception));
    }
}
