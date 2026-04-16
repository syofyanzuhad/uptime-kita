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
use Illuminate\Support\Facades\Log;

class CalculateMonitorStatisticsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout

    public $tries = 3;

    public $backoff = [60, 300, 900]; // key change: avoid rapid retry storms

    /**
     * The number of seconds after which the job's unique lock will be released.
     * This should be longer than the timeout + backoff time to prevent duplicates.
     *
     * @var int
     */
    public $uniqueFor = 1800; // Increased to 30 minutes to cover backoffs

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
     * This ensures only one job per monitor (or one global job when monitorId is null) can exist.
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

            // Global dispatch path: fan out to individual jobs to avoid timeouts
            $monitorCount = 0;
            Monitor::where('is_public', true)
                ->where('uptime_check_enabled', true)
                ->select(['id'])
                ->chunk(100, function ($monitors) use (&$monitorCount) {
                    foreach ($monitors as $monitor) {
                        self::dispatch($monitor->id);
                        $monitorCount++;
                    }
                });

            Log::info("Dispatched statistics calculation jobs for {$monitorCount} monitor(s).");
        } catch (\Throwable $e) {
            report($e); // Ensure the original exception is visible in logs/Horizon
            throw $e;   // Rethrow to allow Laravel to handle retries/failure properly
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

        // Single aggregation query for all periods
        $stats = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $periods['90d'])
            ->selectRaw("
                COUNT(*) as total_90d,
                SUM(CASE WHEN uptime_status = 'up' THEN 1 ELSE 0 END) as up_90d,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_30d,
                SUM(CASE WHEN created_at >= ? AND uptime_status = 'up' THEN 1 ELSE 0 END) as up_30d,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_7d,
                SUM(CASE WHEN created_at >= ? AND uptime_status = 'up' THEN 1 ELSE 0 END) as up_7d,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_24h,
                SUM(CASE WHEN created_at >= ? AND uptime_status = 'up' THEN 1 ELSE 0 END) as up_24h,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_1h,
                SUM(CASE WHEN created_at >= ? AND uptime_status = 'up' THEN 1 ELSE 0 END) as up_1h,
                SUM(CASE WHEN created_at >= ? AND uptime_status != 'up' THEN 1 ELSE 0 END) as incidents_24h,
                SUM(CASE WHEN created_at >= ? AND uptime_status != 'up' THEN 1 ELSE 0 END) as incidents_7d,
                SUM(CASE WHEN created_at >= ? AND uptime_status != 'up' THEN 1 ELSE 0 END) as incidents_30d,
                AVG(CASE WHEN created_at >= ? AND response_time IS NOT NULL THEN response_time END) as avg_resp_24h,
                MIN(CASE WHEN created_at >= ? AND response_time IS NOT NULL THEN response_time END) as min_resp_24h,
                MAX(CASE WHEN created_at >= ? AND response_time IS NOT NULL THEN response_time END) as max_resp_24h
            ", [
                $periods['30d'], $periods['30d'],
                $periods['7d'], $periods['7d'],
                $periods['24h'], $periods['24h'],
                $periods['1h'], $periods['1h'],
                $periods['24h'],
                $periods['7d'],
                $periods['30d'],
                $periods['24h'], $periods['24h'], $periods['24h'],
            ])
            ->first();

        $calculateUptime = function ($up, $total) {
            return ($total > 0) ? round(($up / $total) * 100, 2) : 100.0;
        };

        // Get recent history for last 100 minutes
        $recentHistory = $this->getRecentHistory($monitor);

        // Upsert statistics record
        MonitorStatistic::updateOrCreate(
            ['monitor_id' => $monitor->id],
            [
                'uptime_1h' => $calculateUptime($stats->up_1h, $stats->total_1h),
                'uptime_24h' => $calculateUptime($stats->up_24h, $stats->total_24h),
                'uptime_7d' => $calculateUptime($stats->up_7d, $stats->total_7d),
                'uptime_30d' => $calculateUptime($stats->up_30d, $stats->total_30d),
                'uptime_90d' => $calculateUptime($stats->up_90d, $stats->total_90d),
                'avg_response_time_24h' => $stats->avg_resp_24h ? (int) round($stats->avg_resp_24h) : null,
                'min_response_time_24h' => $stats->min_resp_24h ? (int) $stats->min_resp_24h : null,
                'max_response_time_24h' => $stats->max_resp_24h ? (int) $stats->max_resp_24h : null,
                'incidents_24h' => (int) $stats->incidents_24h,
                'incidents_7d' => (int) $stats->incidents_7d,
                'incidents_30d' => (int) $stats->incidents_30d,
                'total_checks_24h' => (int) $stats->total_24h,
                'total_checks_7d' => (int) $stats->total_7d,
                'total_checks_30d' => (int) $stats->total_30d,
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
