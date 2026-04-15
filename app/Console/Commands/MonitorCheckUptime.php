<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\UptimeMonitor\Commands\CheckUptime as SpatieCheckUptime;
use Throwable;
use Illuminate\Support\Facades\Log;

class MonitorCheckUptime extends SpatieCheckUptime
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $status = parent::handle();
            return (int) ($status ?? self::SUCCESS);
        } catch (Throwable $e) {
            Log::error('monitor:check-uptime failed', [
                'exception' => $e,
            ]);

            $this->error("Uptime check failed: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}
