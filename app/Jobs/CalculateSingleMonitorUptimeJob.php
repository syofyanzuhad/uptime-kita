<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Models\MonitorUptimeDaily;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CalculateSingleMonitorUptimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $monitorId;
    public string $date;

    /**
     * Create a new job instance.
     */
    public function __construct(int $monitorId, ?string $date = null)
    {
        $this->monitorId = $monitorId;
        $this->date = $date ?? Carbon::today()->toDateString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Use a single database query to get both total and up counts
        $result = DB::table('monitor_histories')
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN uptime_status = ? THEN 1 ELSE 0 END) as up_checks
            ', ['up'])
            ->where('monitor_id', $this->monitorId)
            ->whereDate('created_at', $this->date)
            ->first();

        // Early return if no checks found
        if (!$result || $result->total_checks === 0) {
            // Still create/update record with 0% uptime if no data
            $this->updateUptimeRecord(0);
            return;
        }

        $uptimePercentage = ($result->up_checks / $result->total_checks) * 100;
        $this->updateUptimeRecord($uptimePercentage);
    }

    /**
     * Update or create the uptime record
     */
    private function updateUptimeRecord(float $uptimePercentage): void
    {
        // delete all records for the monitor on this date first
        MonitorUptimeDaily::where('monitor_id', $this->monitorId)
            ->where('date', $this->date)
            ->delete();

        // Then create or update the record
        MonitorUptimeDaily::updateOrCreate(
            [
                'monitor_id' => $this->monitorId,
                'date' => $this->date,
            ],
            [
                'uptime_percentage' => round($uptimePercentage, 2), // Round to 2 decimal places
            ]
        );
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure or send notification
        Log::error('Failed to calculate uptime for monitor', [
            'monitor_id' => $this->monitorId,
            'date' => $this->date,
            'error' => $exception->getMessage()
        ]);
    }
}
