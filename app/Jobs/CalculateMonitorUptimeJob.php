<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorUptime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CalculateMonitorUptimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $unit;

    /**
     * Create a new job instance.
     */
    public function __construct(string $unit)
    {
        $this->unit = $unit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $monitors = Monitor::all();
        foreach ($monitors as $monitor) {
            // Calculate uptime percentage for the given unit
            $uptime = $this->calculateUptime($monitor, $this->unit);
            MonitorUptime::updateOrCreate(
                [
                    'monitor_id' => $monitor->id,
                    'unit' => $this->unit,
                ],
                [
                    'uptime_percentage' => $uptime,
                ]
            );
        }
    }

    protected function calculateUptime(Monitor $monitor, string $unit): float
    {
        $now = now();
        switch ($unit) {
            case 'DAILY':
                $from = $now->copy()->subDay();
                break;
            case 'WEEKLY':
                $from = $now->copy()->subWeek();
                break;
            case 'MONTHLY':
                $from = $now->copy()->subMonth();
                break;
            case 'YEARLY':
                $from = $now->copy()->subYear();
                break;
            default:
                $from = $now->copy()->subDays(365); // Default to a year if unit is not recognized
                break;
        }

        $query = $monitor->histories()->where('created_at', '>=', $from);
        $total = $query->count();
        if ($total === 0) return 0.0;

        $up = $query->where('uptime_status', 'up')->count();
        return round(($up / $total) * 100, 2);
    }
}
