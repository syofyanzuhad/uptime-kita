<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorStatistic;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateMonitorStatisticsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout

    public $tries = 3;

    public $backoff = [60, 300, 900];

    /**
     * The number of seconds after which the job's unique lock will be released.
     * This should be longer than the timeout + backoff time to prevent duplicates.
     *
     * @var int
     */
    public $uniqueFor = 1800; // 30 minutes

    protected ?int $monitorId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $monitorId = null)
    {
        $this->monitorId = $monitorId;
        $this->onQueue('statistics'); // Use dedicated queue for statistics
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->monitorId ? 'monitor-'.$this->monitorId : 'all-monitors';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->monitorId) {
                $monitor = Monitor::where('id', $this->monitorId)
                    ->where('is_public', true)
                    ->first();

                if (! $monitor) {
                    return;
                }

                $this->calculateStatistics($monitor);

                return;
            }

            // Global execution path: process all monitors sequentially within this single job
            // This eliminates the overhead of dispatching and processing hundreds of individual jobs
            $monitorCount = 0;
            Monitor::where('is_public', true)
                ->where('uptime_check_enabled', true)
                ->select(['id', 'url']) // Only select what's needed for the chunk
                ->chunkById(100, function ($monitors) use (&$monitorCount) {
                    foreach ($monitors as $monitor) {
                        $this->calculateStatistics($monitor);
                        $monitorCount++;
                    }
                });

            Log::info("Calculated statistics for {$monitorCount} monitor(s) within a single job.");
        } catch (\Throwable $e) {
            report($e);
            throw $e;
        }
    }

    private function calculateStatistics(Monitor $monitor): void
    {
        $now = now();
        $periods = [
            '1h' => $now->copy()->subHour(),
            '24h' => $now->copy()->subDay(),
            '7d' => $now->copy()->subDays(7),
            '30d' => $now->copy()->subDays(30),
            '90d' => $now->copy()->subDays(90),
        ];

        // 1. Calculate 1h and 24h stats using the raw history table (fast, max 1440 rows per monitor)
        $stats24h = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $periods['24h'])
            ->selectRaw("
                COUNT(*) as total_24h,
                SUM(CASE WHEN uptime_status = 'up' THEN 1 ELSE 0 END) as up_24h,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_1h,
                SUM(CASE WHEN created_at >= ? AND uptime_status = 'up' THEN 1 ELSE 0 END) as up_1h,
                SUM(CASE WHEN uptime_status != 'up' THEN 1 ELSE 0 END) as incidents_24h,
                AVG(CASE WHEN response_time IS NOT NULL THEN response_time END) as avg_resp_24h,
                MIN(CASE WHEN response_time IS NOT NULL THEN response_time END) as min_resp_24h,
                MAX(CASE WHEN response_time IS NOT NULL THEN response_time END) as max_resp_24h
            ", [$periods['1h'], $periods['1h']])
            ->first();

        // 2. Calculate 7d, 30d, 90d stats using the daily rollups table (extremely fast, max 90 rows per monitor)
        $dailyStats = DB::table('monitor_uptime_dailies')
            ->where('monitor_id', $monitor->id)
            ->where('date', '>=', $periods['90d']->toDateString())
            ->selectRaw('
                SUM(CASE WHEN date >= ? THEN total_checks ELSE 0 END) as total_7d,
                SUM(CASE WHEN date >= ? THEN (total_checks - failed_checks) ELSE 0 END) as up_7d,
                SUM(CASE WHEN date >= ? THEN failed_checks ELSE 0 END) as incidents_7d,
                SUM(CASE WHEN date >= ? THEN total_checks ELSE 0 END) as total_30d,
                SUM(CASE WHEN date >= ? THEN (total_checks - failed_checks) ELSE 0 END) as up_30d,
                SUM(CASE WHEN date >= ? THEN failed_checks ELSE 0 END) as incidents_30d,
                SUM(total_checks) as total_90d,
                SUM(total_checks - failed_checks) as up_90d
            ', [
                $periods['7d']->toDateString(), $periods['7d']->toDateString(), $periods['7d']->toDateString(),
                $periods['30d']->toDateString(), $periods['30d']->toDateString(), $periods['30d']->toDateString(),
            ])
            ->first();

        $calculateUptime = function ($up, $total) {
            return ($total > 0) ? round(((float) $up / (float) $total) * 100, 2) : 100.0;
        };

        // Get recent history for last 100 minutes
        $recentHistory = $this->getRecentHistory($monitor);

        // Upsert statistics record
        MonitorStatistic::updateOrCreate(
            ['monitor_id' => $monitor->id],
            [
                'uptime_1h' => $calculateUptime($stats24h->up_1h, $stats24h->total_1h),
                'uptime_24h' => $calculateUptime($stats24h->up_24h, $stats24h->total_24h),
                'uptime_7d' => $calculateUptime($dailyStats->up_7d, $dailyStats->total_7d),
                'uptime_30d' => $calculateUptime($dailyStats->up_30d, $dailyStats->total_30d),
                'uptime_90d' => $calculateUptime($dailyStats->up_90d, $dailyStats->total_90d),
                'avg_response_time_24h' => $stats24h->avg_resp_24h ? (int) round($stats24h->avg_resp_24h) : null,
                'min_response_time_24h' => $stats24h->min_resp_24h ? (int) $stats24h->min_resp_24h : null,
                'max_response_time_24h' => $stats24h->max_resp_24h ? (int) $stats24h->max_resp_24h : null,
                'incidents_24h' => (int) $stats24h->incidents_24h,
                'incidents_7d' => (int) $dailyStats->incidents_7d,
                'incidents_30d' => (int) $dailyStats->incidents_30d,
                'total_checks_24h' => (int) $stats24h->total_24h,
                'total_checks_7d' => (int) $dailyStats->total_7d,
                'total_checks_30d' => (int) $dailyStats->total_30d,
                'recent_history_100m' => $recentHistory,
                'calculated_at' => $now,
            ]
        );

        Log::debug("Statistics calculated for monitor {$monitor->id} ({$monitor->url})");
    }

    private function getRecentHistory(Monitor $monitor): array
    {
        $oneHundredMinutesAgo = now()->subMinutes(100);

        // Optimized retrieval - covering index will be used
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $oneHundredMinutesAgo)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->select(['created_at', 'uptime_status', 'response_time', 'message'])
            ->get();

        // Transform to a lighter format for JSON storage
        return $histories->map(function ($history) {
            return [
                'created_at' => $history->created_at->toISOString(),
                'uptime_status' => $history->uptime_status,
                'response_time' => $history->response_time,
                'message' => $history->message,
            ];
        })->toArray();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateMonitorStatisticsJob failed', [
            'monitor_id' => $this->monitorId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
