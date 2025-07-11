<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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
        $today = Carbon::today();

        // Process monitors in chunks for better memory management
        Monitor::chunk(50, function ($monitors) {
            foreach ($monitors as $monitor) {
                // Dispatch a job for each monitor
                \App\Jobs\CalculateSingleMonitorUptimeJob::dispatch($monitor->id);
            }
        });
    }
}
