<?php

namespace App\Console\Commands;

use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateDailyUptimeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'uptime:calculate-daily {date? : The date to calculate uptime for (Y-m-d format, defaults to today)} {--monitor-id= : Calculate for specific monitor ID only} {--force : Force recalculation even if already calculated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate daily uptime for all monitors or a specific monitor for a given date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = $this->argument('date') ?? Carbon::today()->toDateString();
        $monitorId = $this->option('monitor-id');
        $force = $this->option('force');

        // Validate monitor ID if provided
        if ($monitorId !== null) {
            if (!is_numeric($monitorId)) {
                $this->error("Invalid monitor ID: {$monitorId}. Monitor ID must be a number.");
                return 1;
            }
            if ((int)$monitorId < 0) {
                $this->error("Invalid monitor ID: {$monitorId}. Monitor ID must be a number.");
                return 1;
            }
        }

        // Validate date format
        if (! $this->isValidDate($date)) {
            $this->error("Invalid date format: {$date}. Please use Y-m-d format (e.g., 2024-01-15)");

            return 1;
        }

        $this->info("Starting daily uptime calculation for date: {$date}");

        try {
            $processedCount = 0;
            
            if ($monitorId) {
                // Calculate for specific monitor
                $result = $this->calculateForSpecificMonitor($monitorId, $date, $force);
                if ($result === false) {
                    return 1;
                }
                $processedCount = $result;
            } else {
                // Calculate for all monitors
                $processedCount = $this->calculateForAllMonitors($date, $force);
            }

            if ($processedCount > 0) {
                $this->info('Daily uptime calculation completed');
                if (!$monitorId) {
                    $this->info("Total monitors processed: {$processedCount}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to dispatch uptime calculation job: {$e->getMessage()}");
            Log::error('CalculateDailyUptimeCommand failed', [
                'date' => $date,
                'monitor_id' => $monitorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Validate date format
     */
    private function isValidDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }
        
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);
            return $parsed && $parsed->format('Y-m-d') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calculate uptime for a specific monitor
     */
    private function calculateForSpecificMonitor(?string $monitorId, string $date, bool $force)
    {
        // Validate monitor exists
        $monitor = Monitor::find($monitorId);
        if (! $monitor) {
            $this->error("Monitor with ID {$monitorId} not found");
            return false;
        }

        $displayName = $monitor->display_name ?: $monitor->url;
        $this->info("Calculating uptime for monitor: {$displayName} (ID: {$monitorId})");

        // Check if calculation already exists (unless force is used)
        if (! $force && $this->calculationExists($monitorId, $date)) {
            $this->warn("Uptime calculation for monitor {$monitorId} on {$date} already exists. Use --force to recalculate.");
            return 0;
        }

        // Dispatch single monitor calculation job
        $job = new CalculateSingleMonitorUptimeJob((int) $monitorId, $date);
        dispatch($job);

        $this->info("Job dispatched for monitor {$monitorId} for date {$date}");
        return 1;
    }

    /**
     * Calculate uptime for all monitors
     */
    private function calculateForAllMonitors(string $date, bool $force): int
    {
        $this->info("Calculating uptime for all monitors for date: {$date}");

        // Get all monitor IDs
        $monitorIds = Monitor::pluck('id')->toArray();

        if (empty($monitorIds)) {
            $this->info('No monitors found to calculate uptime for');
            return 0;
        }

        $this->info('Found '.count($monitorIds).' monitors to process');

        // If force is used, we'll process all monitors
        // Otherwise, we'll skip monitors that already have calculations
        $monitorsToProcess = $force ? $monitorIds : $this->getMonitorsWithoutCalculation($monitorIds, $date);

        if (empty($monitorsToProcess)) {
            $this->info('All monitors already have uptime calculations for this date. Use --force to recalculate.');
            return 0;
        }

        $this->info('Processing '.count($monitorsToProcess).' monitors');

        // Dispatch jobs for each monitor
        foreach ($monitorsToProcess as $monitorId) {
            $job = new CalculateSingleMonitorUptimeJob($monitorId, $date);
            dispatch($job);
        }

        $this->info('Dispatched '.count($monitorsToProcess).' calculation jobs');
        return count($monitorsToProcess);
    }

    /**
     * Check if calculation already exists for a monitor and date
     */
    private function calculationExists(string $monitorId, string $date): bool
    {
        return \DB::table('monitor_uptime_dailies')
            ->where('monitor_id', $monitorId)
            ->where('date', $date)
            ->exists();
    }

    /**
     * Get monitors that don't have calculations for the given date
     */
    private function getMonitorsWithoutCalculation(array $monitorIds, string $date): array
    {
        $existingCalculations = \DB::table('monitor_uptime_dailies')
            ->whereIn('monitor_id', $monitorIds)
            ->where('date', $date)
            ->pluck('monitor_id')
            ->toArray();

        return array_diff($monitorIds, $existingCalculations);
    }
}
