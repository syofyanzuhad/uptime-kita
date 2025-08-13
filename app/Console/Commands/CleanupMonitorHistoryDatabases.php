<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitor;
use App\Services\MonitorHistoryDatabaseService;

class CleanupMonitorHistoryDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:cleanup-history
                            {--days=30 : Number of days to keep history records}
                            {--monitor-id= : Specific monitor ID to cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old monitor history records from SQLite databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $monitorId = $this->option('monitor-id');

        if ($days < 1 || $days > 365) {
            $this->error('Days must be between 1 and 365');
            return 1;
        }

        $service = new MonitorHistoryDatabaseService();

        if ($monitorId) {
            // Cleanup specific monitor
            $monitor = Monitor::find($monitorId);
            if (!$monitor) {
                $this->error("Monitor with ID {$monitorId} not found");
                return 1;
            }

            $this->cleanupMonitor($service, $monitor, $days);
        } else {
            // Cleanup all monitors
            $this->cleanupAllMonitors($service, $days);
        }

        return 0;
    }

    /**
     * Cleanup a specific monitor
     */
    private function cleanupMonitor(MonitorHistoryDatabaseService $service, Monitor $monitor, int $days): void
    {
        $this->info("Cleaning up history for monitor {$monitor->id} ({$monitor->url})...");

        if (!$service->monitorDatabaseExists($monitor->id)) {
            $this->warn("No database found for monitor {$monitor->id}");
            return;
        }

        $deletedCount = $service->cleanupOldHistory($monitor->id, $days);
        $this->info("Deleted {$deletedCount} old records from monitor {$monitor->id}");
    }

    /**
     * Cleanup all monitors
     */
    private function cleanupAllMonitors(MonitorHistoryDatabaseService $service, int $days): void
    {
        $this->info("Cleaning up history records older than {$days} days for all monitors...");

        $monitors = Monitor::all();
        $bar = $this->output->createProgressBar($monitors->count());
        $bar->start();

        $totalDeleted = 0;
        $processed = 0;
        $failed = 0;

        foreach ($monitors as $monitor) {
            try {
                if ($service->monitorDatabaseExists($monitor->id)) {
                    $deleted = $service->cleanupOldHistory($monitor->id, $days);
                    $totalDeleted += $deleted;
                }
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to cleanup monitor {$monitor->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Summary:");
        $this->line("Processed: {$processed} monitors");
        $this->line("Failed: {$failed} monitors");
        $this->line("Total records deleted: {$totalDeleted}");
    }
}
