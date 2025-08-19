<?php

namespace App\Jobs;

use App\Services\MonitorPerformanceService;
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
        // Use efficient date range queries instead of whereDate()
        $startDate = Carbon::parse($this->date)->startOfDay();
        $endDate = $startDate->copy()->endOfDay();

        // Use a single database query to get both total and up counts
        $result = DB::table('monitor_histories')
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as up_checks
            ')
            ->where('monitor_id', $this->monitorId)
            ->whereBetween('created_at', [$startDate, $endDate])
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
            $this->updateUptimeRecord(0, []);

            return;
        }

        $uptimePercentage = ($result->up_checks / $result->total_checks) * 100;

        // Calculate response time metrics
        $performanceService = app(MonitorPerformanceService::class);
        $responseMetrics = $performanceService->aggregateDailyMetrics($this->monitorId, $this->date);

        Log::debug('Uptime calculation details', [
            'monitor_id' => $this->monitorId,
            'date' => $this->date,
            'total_checks' => $result->total_checks,
            'up_checks' => $result->up_checks,
            'uptime_percentage' => $uptimePercentage,
            'response_metrics' => $responseMetrics,
        ]);

        $this->updateUptimeRecord($uptimePercentage, $responseMetrics);
    }

    /**
     * Update or create the uptime record using efficient updateOrInsert
     */
    private function updateUptimeRecord(float $uptimePercentage, array $responseMetrics = []): void
    {
        // Ensure date is in correct format (Y-m-d, not datetime)
        $dateOnly = Carbon::parse($this->date)->toDateString();
        $roundedPercentage = round($uptimePercentage, 2);

        // Prepare data for upsert
        $data = [
            'uptime_percentage' => $roundedPercentage,
            'updated_at' => now(),
        ];

        // Add response metrics if available
        if (! empty($responseMetrics)) {
            $data['avg_response_time'] = $responseMetrics['avg_response_time'];
            $data['min_response_time'] = $responseMetrics['min_response_time'];
            $data['max_response_time'] = $responseMetrics['max_response_time'];
            $data['total_checks'] = $responseMetrics['total_checks'];
            $data['failed_checks'] = $responseMetrics['failed_checks'];
        }

        // For new records, add created_at
        $insertData = array_merge($data, [
            'created_at' => now(),
        ]);

        try {
            // Use Laravel's efficient updateOrInsert method
            DB::table('monitor_uptime_dailies')
                ->updateOrInsert(
                    [
                        'monitor_id' => $this->monitorId,
                        'date' => $dateOnly,
                    ],
                    $insertData
                );

            Log::debug('Uptime record updated/created successfully', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'uptime_percentage' => $roundedPercentage,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in uptime record operation', [
                'monitor_id' => $this->monitorId,
                'date' => $dateOnly,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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
