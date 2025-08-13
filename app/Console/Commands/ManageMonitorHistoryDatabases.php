<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Services\MonitorHistoryDatabaseService;

class ManageMonitorHistoryDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:history-databases
                            {action : Action to perform (create-all, cleanup-all, delete-all, status)}
                            {--monitor-id= : Specific monitor ID to operate on}
                            {--days=30 : Number of days to keep for cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage SQLite databases for monitor histories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $monitorId = $this->option('monitor-id');
        $days = (int) $this->option('days');

        $service = new MonitorHistoryDatabaseService();

        switch ($action) {
            case 'create-all':
                $this->createAllDatabases($service);
                break;
            case 'cleanup-all':
                $this->cleanupAllDatabases($service, $days);
                break;
            case 'delete-all':
                $this->deleteAllDatabases($service);
                break;
            case 'status':
                $this->showStatus($service);
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Create databases for all monitors
     */
    private function createAllDatabases(MonitorHistoryDatabaseService $service): void
    {
        $this->info('Creating SQLite databases for all monitors...');

        $monitors = Monitor::all();
        $bar = $this->output->createProgressBar($monitors->count());
        $bar->start();

        $created = 0;
        $failed = 0;

        foreach ($monitors as $monitor) {
            if (MonitorHistory::ensureMonitorDatabase($monitor->id)) {
                $created++;
            } else {
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created: {$created}, Failed: {$failed}");
    }

    /**
     * Cleanup old history records from all databases
     */
    private function cleanupAllDatabases(MonitorHistoryDatabaseService $service, int $days): void
    {
        $this->info("Cleaning up history records older than {$days} days...");

        $monitors = Monitor::all();
        $bar = $this->output->createProgressBar($monitors->count());
        $bar->start();

        $totalDeleted = 0;
        $processed = 0;

        foreach ($monitors as $monitor) {
            if (MonitorHistory::monitorHasDatabase($monitor->id)) {
                $deleted = MonitorHistory::cleanupForMonitor($monitor->id, $days);
                $totalDeleted += $deleted;
            }
            $processed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Processed: {$processed} monitors, Total records deleted: {$totalDeleted}");
    }

    /**
     * Delete all monitor history databases
     */
    private function deleteAllDatabases(MonitorHistoryDatabaseService $service): void
    {
        if (!$this->confirm('Are you sure you want to delete ALL monitor history databases? This action cannot be undone.')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->info('Deleting all monitor history databases...');

        $monitors = Monitor::all();
        $bar = $this->output->createProgressBar($monitors->count());
        $bar->start();

        $deleted = 0;
        $failed = 0;

        foreach ($monitors as $monitor) {
            if ($service->deleteMonitorDatabase($monitor->id)) {
                $deleted++;
            } else {
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Deleted: {$deleted}, Failed: {$failed}");
    }

    /**
     * Show status of monitor history databases
     */
    private function showStatus(MonitorHistoryDatabaseService $service): void
    {
        $this->info('Monitor History Databases Status');
        $this->newLine();

        $monitors = Monitor::all();
        $totalMonitors = $monitors->count();
        $databasesExist = 0;
        $totalRecords = 0;

        $headers = ['Monitor ID', 'URL', 'Database Exists', 'Records Count'];
        $rows = [];

        foreach ($monitors as $monitor) {
            $exists = MonitorHistory::monitorHasDatabase($monitor->id);
            if ($exists) {
                $databasesExist++;
                $records = count(MonitorHistory::getForMonitor($monitor->id, 1000, 0));
                $totalRecords += $records;
            } else {
                $records = 0;
            }

            $rows[] = [
                $monitor->id,
                $monitor->url,
                $exists ? 'Yes' : 'No',
                $records,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("Summary:");
        $this->line("Total Monitors: {$totalMonitors}");
        $this->line("Databases Created: {$databasesExist}");
        $this->line("Total History Records: {$totalRecords}");
    }
}
