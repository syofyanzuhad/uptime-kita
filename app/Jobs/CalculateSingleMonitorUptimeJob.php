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

class CalculateSingleMonitorUptimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $monitorId;

    /**
     * Create a new job instance.
     */
    public function __construct($monitorId)
    {
        $this->monitorId = $monitorId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::today();
        $monitor = Monitor::find($this->monitorId);
        if (!$monitor) {
            return;
        }
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
}
