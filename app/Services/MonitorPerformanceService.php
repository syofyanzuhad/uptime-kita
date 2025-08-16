<?php

namespace App\Services;

use App\Models\MonitorHistory;
use App\Models\MonitorPerformanceHourly;
use Carbon\Carbon;

class MonitorPerformanceService
{
    /**
     * Update hourly performance metrics for a monitor.
     */
    public function updateHourlyMetrics(int $monitorId, ?int $responseTime, bool $isSuccess): void
    {
        $hour = Carbon::now()->startOfHour();

        // Find or create hourly record
        $performance = MonitorPerformanceHourly::firstOrNew([
            'monitor_id' => $monitorId,
            'hour' => $hour,
        ]);

        // Update counts
        if ($isSuccess) {
            $performance->success_count = ($performance->success_count ?? 0) + 1;
        } else {
            $performance->failure_count = ($performance->failure_count ?? 0) + 1;
        }

        // Update response time metrics if available
        if ($responseTime !== null && $isSuccess) {
            $this->updateResponseTimeMetrics($performance, $responseTime);
        }

        $performance->save();
    }

    /**
     * Update response time metrics for the hourly performance record.
     */
    protected function updateResponseTimeMetrics(MonitorPerformanceHourly $performance, int $responseTime): void
    {
        // Get all response times for this hour
        $responseTimes = MonitorHistory::where('monitor_id', $performance->monitor_id)
            ->where('checked_at', '>=', $performance->hour)
            ->where('checked_at', '<', $performance->hour->copy()->addHour())
            ->whereNotNull('response_time')
            ->where('uptime_status', 'up')
            ->pluck('response_time')
            ->toArray();

        if (empty($responseTimes)) {
            $performance->avg_response_time = $responseTime;
            $performance->p95_response_time = $responseTime;
            $performance->p99_response_time = $responseTime;

            return;
        }

        // Calculate average
        $performance->avg_response_time = array_sum($responseTimes) / count($responseTimes);

        // Calculate percentiles
        sort($responseTimes);
        $performance->p95_response_time = $this->calculatePercentile($responseTimes, 95);
        $performance->p99_response_time = $this->calculatePercentile($responseTimes, 99);
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

        $histories = MonitorHistory::where('monitor_id', $monitorId)
            ->whereBetween('checked_at', [$startDate, $endDate])
            ->whereNotNull('response_time')
            ->where('uptime_status', 'up')
            ->get();

        if ($histories->isEmpty()) {
            return [
                'avg_response_time' => null,
                'min_response_time' => null,
                'max_response_time' => null,
                'total_checks' => 0,
                'failed_checks' => 0,
            ];
        }

        $responseTimes = $histories->pluck('response_time')->filter()->toArray();
        $totalChecks = MonitorHistory::where('monitor_id', $monitorId)
            ->whereBetween('checked_at', [$startDate, $endDate])
            ->count();

        $failedChecks = MonitorHistory::where('monitor_id', $monitorId)
            ->whereBetween('checked_at', [$startDate, $endDate])
            ->where('uptime_status', 'down')
            ->count();

        return [
            'avg_response_time' => ! empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : null,
            'min_response_time' => ! empty($responseTimes) ? min($responseTimes) : null,
            'max_response_time' => ! empty($responseTimes) ? max($responseTimes) : null,
            'total_checks' => $totalChecks,
            'failed_checks' => $failedChecks,
        ];
    }

    /**
     * Get response time statistics for a monitor in a date range.
     */
    public function getResponseTimeStats(int $monitorId, Carbon $startDate, Carbon $endDate): array
    {
        $histories = MonitorHistory::where('monitor_id', $monitorId)
            ->whereBetween('checked_at', [$startDate, $endDate])
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
