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
        Monitor::chunk(50, function ($monitors) use ($today) {
            foreach ($monitors as $monitor) {
                $histories = $monitor->histories()
                    ->whereDate('created_at', $today)
                    ->get();
                $totalChecks = $histories->count();
                $upChecks = $histories->where('uptime_status', 'up')->count();
                $uptimePercentage = $totalChecks > 0 ? ($upChecks / $totalChecks) * 100 : 0;

                MonitorUptimeDaily::updateOrCreate(
                    [
                        'monitor_id' => $monitor->id,
                        'date' => $today,
                    ],
                    [
                        'uptime_percentage' => $uptimePercentage,
                    ]
                );
            }
        });
    }
}
