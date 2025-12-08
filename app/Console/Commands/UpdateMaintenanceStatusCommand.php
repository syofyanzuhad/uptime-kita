<?php

namespace App\Console\Commands;

use App\Services\MaintenanceWindowService;
use Illuminate\Console\Command;

class UpdateMaintenanceStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:update-maintenance-status
                            {--cleanup : Also cleanup expired one-time maintenance windows}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update maintenance status for all monitors based on their maintenance windows';

    /**
     * Execute the console command.
     */
    public function handle(MaintenanceWindowService $maintenanceService): int
    {
        $this->info('Updating maintenance status for monitors...');

        $updated = $maintenanceService->updateAllMaintenanceStatuses();

        $this->info("Updated {$updated} monitor(s) maintenance status.");

        if ($this->option('cleanup')) {
            $this->info('Cleaning up expired one-time maintenance windows...');
            $cleaned = $maintenanceService->cleanupExpiredWindows();
            $this->info("Cleaned up {$cleaned} monitor(s) with expired windows.");
        }

        return self::SUCCESS;
    }
}
