<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorStatistic;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateMonitorStatisticsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes timeout

    public $tries = 3;

    public $backoff = [60, 120, 300]; // Exponential backoff: 1 min, 2 min, 5 min

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
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->monitorId) {
            $monitors = Monitor::where('id', $this->monitorId)
                ->where('is_public', true)
                ->get();
        } else {
            $monitors = Monitor::where('is_public', true)
                ->where('uptime_check_enabled', true)
                ->get();
        }

        if ($monitors->isEmpty()) {
            Log::info('No public monitors found for statistics calculation.');

            return;
        }

        Log::info("Calculating statistics for {$monitors->count()} monitor(s)...");

        foreach ($monitors as $monitor) {
            try {
                $this->calculateStatistics($monitor);
            } catch (\Exception $e) {
                Log::error("Failed to calculate statistics for monitor {$monitor->id}", [
                    'monitor_id' => $monitor->id,
                    'monitor_url' => $monitor->url,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Continue with other monitors even if one fails
                continue;
            }
        }

        Log::info('Monitor statistics calculation completed successfully!');
    }

    private function calculateStatistics(Monitor $monitor): void
    {
        $now = now();

        // Calculate uptime percentages for different periods
        $uptimeStats = [
            '1h' => $this->calculateUptimePercentage($monitor, $now->copy()->subHour()),
            '24h' => $this->calculateUptimePercentage($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(30)),
            '90d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(90)),
        ];

        // Calculate response time stats for last 24h
        $responseTimeStats = $this->calculateResponseTimeStats($monitor, $now->copy()->subDay());

        // Calculate incident counts
        $incidentCounts = [
            '24h' => $this->calculateIncidentCount($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateIncidentCount($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateIncidentCount($monitor, $now->copy()->subDays(30)),
        ];

        // Calculate total checks
        $totalChecks = [
            '24h' => $this->calculateTotalChecks($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateTotalChecks($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateTotalChecks($monitor, $now->copy()->subDays(30)),
        ];

        // Get recent history for last 100 minutes
        $recentHistory = $this->getRecentHistory($monitor);

        // Upsert statistics record
        MonitorStatistic::updateOrCreate(
            ['monitor_id' => $monitor->id],
            [
                'uptime_1h' => $uptimeStats['1h'],
                'uptime_24h' => $uptimeStats['24h'],
                'uptime_7d' => $uptimeStats['7d'],
                'uptime_30d' => $uptimeStats['30d'],
                'uptime_90d' => $uptimeStats['90d'],
                'avg_response_time_24h' => $responseTimeStats['avg'],
                'min_response_time_24h' => $responseTimeStats['min'],
                'max_response_time_24h' => $responseTimeStats['max'],
                'incidents_24h' => $incidentCounts['24h'],
                'incidents_7d' => $incidentCounts['7d'],
                'incidents_30d' => $incidentCounts['30d'],
                'total_checks_24h' => $totalChecks['24h'],
                'total_checks_7d' => $totalChecks['7d'],
                'total_checks_30d' => $totalChecks['30d'],
                'recent_history_100m' => $recentHistory,
                'calculated_at' => $now,
            ]
        );

        Log::debug("Statistics calculated for monitor {$monitor->id} ({$monitor->url})");
    }

    private function calculateUptimePercentage(Monitor $monitor, Carbon $startDate): float
    {
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->select('uptime_status')
            ->get();

        if ($histories->isEmpty()) {
            return 100.0;
        }

        $upCount = $histories->where('uptime_status', 'up')->count();
        $totalCount = $histories->count();

        return round(($upCount / $totalCount) * 100, 2);
    }

    private function calculateResponseTimeStats(Monitor $monitor, Carbon $startDate): array
    {
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('response_time')
            ->pluck('response_time');

        if ($histories->isEmpty()) {
            return ['avg' => null, 'min' => null, 'max' => null];
        }

        return [
            'avg' => (int) round($histories->avg()),
            'min' => $histories->min(),
            'max' => $histories->max(),
        ];
    }

    private function calculateIncidentCount(Monitor $monitor, Carbon $startDate): int
    {
        return MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->where('uptime_status', '!=', 'up')
            ->count();
    }

    private function calculateTotalChecks(Monitor $monitor, Carbon $startDate): int
    {
        return MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->count();
    }

    private function getRecentHistory(Monitor $monitor): array
    {
        $oneHundredMinutesAgo = now()->subMinutes(100);

        // Get unique history IDs using raw SQL to ensure only one record per minute
        $sql = "
            SELECT id FROM (
                SELECT id, created_at, ROW_NUMBER() OVER (
                    PARTITION BY monitor_id, strftime('%Y-%m-%d %H:%M', created_at) 
                    ORDER BY created_at DESC, id DESC
                ) as rn
                FROM monitor_histories
                WHERE monitor_id = ?
            ) ranked
            WHERE rn = 1
            ORDER BY created_at DESC
            LIMIT 100
        ";

        $uniqueIds = DB::select($sql, [$monitor->id]);
        $ids = array_column($uniqueIds, 'id');

        // Get unique histories and filter by time
        $histories = MonitorHistory::whereIn('id', $ids)
            ->where('created_at', '>=', $oneHundredMinutesAgo)
            ->orderBy('created_at', 'desc')
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
