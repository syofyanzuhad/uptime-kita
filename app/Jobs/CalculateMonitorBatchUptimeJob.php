<?php

namespace App\Jobs;

use App\Services\MonitorPerformanceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CalculateMonitorBatchUptimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    /**
     * @param  array<int>  $monitorIds
     */
    public function __construct(
        public array $monitorIds,
        public string $date
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(MonitorPerformanceService $performanceService): void
    {
        if (empty($this->monitorIds)) {
            return;
        }

        $startDate = Carbon::parse($this->date)->startOfDay();
        $endDate = $startDate->copy()->endOfDay();
        $dateOnly = Carbon::parse($this->date)->toDateString();

        // 1. Query aggregate statistics for all monitors in this chunk in a single query
        $historyStats = DB::table('monitor_histories')
            ->selectRaw('
                monitor_id,
                COUNT(*) as total_checks,
                SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as up_checks
            ')
            ->whereIn('monitor_id', $this->monitorIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('monitor_id')
            ->get()
            ->keyBy('monitor_id');

        $recordsToUpsert = [];

        foreach ($this->monitorIds as $monitorId) {
            try {
                $stat = $historyStats->get($monitorId);
                $totalChecks = (int) ($stat->total_checks ?? 0);
                $upChecks = (int) ($stat->up_checks ?? 0);

                $uptimePercentage = $totalChecks > 0
                    ? round(($upChecks / $totalChecks) * 100, 2)
                    : 0.0;

                $responseMetrics = $totalChecks > 0
                    ? $performanceService->aggregateDailyMetrics($monitorId, $this->date)
                    : [];

                $record = [
                    'monitor_id' => $monitorId,
                    'date' => $dateOnly,
                    'uptime_percentage' => $uptimePercentage,
                    'total_checks' => $totalChecks,
                    'failed_checks' => $totalChecks - $upChecks,
                    'avg_response_time' => $responseMetrics['avg_response_time'] ?? null,
                    'min_response_time' => $responseMetrics['min_response_time'] ?? null,
                    'max_response_time' => $responseMetrics['max_response_time'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $recordsToUpsert[] = $record;
            } catch (Throwable $e) {
                Log::error("Failed calculating daily uptime for monitor {$monitorId} on {$this->date}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! empty($recordsToUpsert)) {
            DB::table('monitor_uptime_dailies')->upsert(
                $recordsToUpsert,
                ['monitor_id', 'date'],
                [
                    'uptime_percentage',
                    'total_checks',
                    'failed_checks',
                    'avg_response_time',
                    'min_response_time',
                    'max_response_time',
                    'updated_at',
                ]
            );
        }
    }
}
