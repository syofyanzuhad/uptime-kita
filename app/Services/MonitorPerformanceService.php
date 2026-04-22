<?php

namespace App\Services;

use App\Models\MonitorHistory;
use App\Models\MonitorPerformanceHourly;
use Carbon\Carbon;

class MonitorPerformanceService
{
    /**
     * Update hourly performance metrics for a monitor.
     * Optimized to avoid expensive database scans on every ping.
     */
    public function updateHourlyMetrics(int $monitorId, ?int $responseTime, bool $isSuccess): void
    {
        $hour = Carbon::now()->startOfHour();

        // Find or create hourly record
        $performance = MonitorPerformanceHourly::firstOrNew([
            'monitor_id' => $monitorId,
            'hour' => $hour,
        ]);

        // Initialize counts if new
        $performance->success_count = $performance->success_count ?? 0;
        $performance->failure_count = $performance->failure_count ?? 0;

        // Update counts
        if ($isSuccess) {
            $performance->success_count++;
        } else {
            $performance->failure_count++;
        }

        // Update response time metrics using a running average (O(1) operation)
        if ($responseTime !== null && $isSuccess) {
            $currentAvg = $performance->avg_response_time ?? 0;
            $count = $performance->success_count;
            
            if ($count === 1) {
                $performance->avg_response_time = $responseTime;
            } else {
                // Running average formula: ((previous_avg * (n-1)) + new_value) / n
                $performance->avg_response_time = (($currentAvg * ($count - 1)) + $responseTime) / $count;
            }
            
            // Note: P95 and P99 are removed from real-time path as they require O(N log N) sorting
            // They can be calculated by a separate background process if needed.
        }

        $performance->save();
    }

    /**
     * Update response time metrics for the hourly performance record.
     * @deprecated Expensive - moved to running average in updateHourlyMetrics
     */
    protected function updateResponseTimeMetrics(MonitorPerformanceHourly $performance, int $responseTime): void
    {
        // Deprecated to save CPU resources
    }

    /**
     * Calculate percentile from sorted array.
     */
    protected function calculatePercentile(array $sortedArray, int $percentile): float
    {
        $count = count($sortedArray);
        $index = ($percentile / 100) * ($count - 1);

        if (floor($index) == $index) {
            return $sortedArray[$index];
        }

        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;

        return $sortedArray[$lower] * (1 - $weight) + $sortedArray[$upper] * $weight;
    }

    /**
     * Aggregate daily performance metrics for a monitor.
     */
    public function aggregateDailyMetrics(int $monitorId, string $date): array
    {
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate = $startDate->copy()->endOfDay();

        // Use created_at instead of checked_at for consistency with the main job
        // and because some records have null checked_at values
        $result = MonitorHistory::where('monitor_id', $monitorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN uptime_status = "down" THEN 1 ELSE 0 END) as failed_checks,
                AVG(CASE WHEN uptime_status = "up" AND response_time IS NOT NULL THEN response_time ELSE NULL END) as avg_response_time,
                MIN(CASE WHEN uptime_status = "up" AND response_time IS NOT NULL THEN response_time ELSE NULL END) as min_response_time,
                MAX(CASE WHEN uptime_status = "up" AND response_time IS NOT NULL THEN response_time ELSE NULL END) as max_response_time
            ')
            ->first();

        return [
            'avg_response_time' => $result->avg_response_time ? round($result->avg_response_time) : null,
            'min_response_time' => $result->min_response_time,
            'max_response_time' => $result->max_response_time,
            'total_checks' => $result->total_checks ?? 0,
            'failed_checks' => $result->failed_checks ?? 0,
        ];
    }

    /**
     * Get response time statistics for a monitor in a date range.
     */
    public function getResponseTimeStats(int $monitorId, Carbon $startDate, Carbon $endDate): array
    {
        $histories = MonitorHistory::where('monitor_id', $monitorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('response_time')
            ->where('uptime_status', 'up')
            ->pluck('response_time')
            ->filter()
            ->toArray();

        if (empty($histories)) {
            return [
                'avg' => 0,
                'min' => 0,
                'max' => 0,
            ];
        }

        return [
            'avg' => round(array_sum($histories) / count($histories)),
            'min' => min($histories),
            'max' => max($histories),
        ];
    }
}
