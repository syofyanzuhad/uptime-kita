<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Services\MonitorHistoryDatabaseService;

class MonitorHistoryController extends Controller
{
    /**
     * Get history records for a specific monitor
     */
    public function index(Request $request, int $monitorId): JsonResponse
    {
        // Check if monitor exists and user has access
        $monitor = Monitor::findOrFail($monitorId);

        // Check authorization
        if (!$monitor->is_public && !auth()->check()) {
            abort(403, 'Access denied');
        }

        if (!$monitor->is_public && auth()->check()) {
            // Check if user is subscribed to this monitor
            if (!$monitor->users->contains(auth()->id())) {
                abort(403, 'Access denied');
            }
        }

        $limit = min((int) $request->get('limit', 100), 1000); // Max 1000 records
        $offset = (int) $request->get('offset', 0);
        $status = $request->get('status'); // Filter by status
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $service = new MonitorHistoryDatabaseService();

        // Ensure database exists
        if (!MonitorHistory::monitorHasDatabase($monitorId)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'limit' => $limit,
                    'offset' => $offset,
                ]
            ]);
        }

        // Get history records
        $records = MonitorHistory::getForMonitor($monitorId, $limit, $offset);

        // Apply filters if provided
        if ($status) {
            $records = array_filter($records, function ($record) use ($status) {
                return $record['uptime_status'] === $status;
            });
        }

        if ($dateFrom) {
            $dateFrom = \Carbon\Carbon::parse($dateFrom);
            $records = array_filter($records, function ($record) use ($dateFrom) {
                return \Carbon\Carbon::parse($record['created_at'])->gte($dateFrom);
            });
        }

        if ($dateTo) {
            $dateTo = \Carbon\Carbon::parse($dateTo);
            $records = array_filter($records, function ($record) use ($dateTo) {
                return \Carbon\Carbon::parse($record['created_at'])->lte($dateTo);
            });
        }

        // Re-index array after filtering
        $records = array_values($records);

        return response()->json([
            'data' => $records,
            'meta' => [
                'total' => count($records),
                'limit' => $limit,
                'offset' => $offset,
                'monitor_id' => $monitorId,
                'monitor_url' => $monitor->url,
            ]
        ]);
    }

    /**
     * Get the latest history record for a specific monitor
     */
    public function latest(int $monitorId): JsonResponse
    {
        // Check if monitor exists and user has access
        $monitor = Monitor::findOrFail($monitorId);

        // Check authorization
        if (!$monitor->is_public && !auth()->check()) {
            abort(403, 'Access denied');
        }

        if (!$monitor->is_public && auth()->check()) {
            // Check if user is subscribed to this monitor
            if (!$monitor->users->contains(auth()->id())) {
                abort(403, 'Access denied');
            }
        }

        $service = new MonitorHistoryDatabaseService();

        // Ensure database exists
        if (!MonitorHistory::monitorHasDatabase($monitorId)) {
            return response()->json([
                'data' => null,
                'meta' => [
                    'monitor_id' => $monitorId,
                    'monitor_url' => $monitor->url,
                ]
            ]);
        }

        $latestRecord = MonitorHistory::scopeLatestByMonitorId(null, $monitorId);

        return response()->json([
            'data' => $latestRecord,
            'meta' => [
                'monitor_id' => $monitorId,
                'monitor_url' => $monitor->url,
            ]
        ]);
    }

    /**
     * Get statistics for a specific monitor
     */
    public function statistics(int $monitorId): JsonResponse
    {
        // Check if monitor exists and user has access
        $monitor = Monitor::findOrFail($monitorId);

        // Check authorization
        if (!$monitor->is_public && !auth()->check()) {
            abort(403, 'Access denied');
        }

        if (!$monitor->is_public && auth()->check()) {
            // Check if user is subscribed to this monitor
            if (!$monitor->users->contains(auth()->id())) {
                abort(403, 'Access denied');
            }
        }

        $service = new MonitorHistoryDatabaseService();

        // Ensure database exists
        if (!MonitorHistory::monitorHasDatabase($monitorId)) {
            return response()->json([
                'data' => [
                    'total_records' => 0,
                    'status_counts' => [
                        'up' => 0,
                        'down' => 0,
                        'not yet checked' => 0,
                    ],
                    'uptime_percentage' => 0,
                    'average_response_time' => 0,
                    'last_check' => null,
                ],
                'meta' => [
                    'monitor_id' => $monitorId,
                    'monitor_url' => $monitor->url,
                ]
            ]);
        }

        // Get all history records for statistics
        $records = MonitorHistory::getForMonitor($monitorId, 10000, 0); // Get up to 10k records for stats

        $totalRecords = count($records);
        $statusCounts = [
            'up' => 0,
            'down' => 0,
            'not yet checked' => 0,
        ];

        $responseTimes = [];
        $lastCheck = null;

        foreach ($records as $record) {
            $status = $record['uptime_status'];
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            if ($record['response_time_ms']) {
                $responseTimes[] = $record['response_time_ms'];
            }

            if (!$lastCheck || $record['created_at'] > $lastCheck) {
                $lastCheck = $record['created_at'];
            }
        }

        // Calculate uptime percentage
        $uptimePercentage = $totalRecords > 0
            ? round(($statusCounts['up'] / $totalRecords) * 100, 2)
            : 0;

        // Calculate average response time
        $averageResponseTime = count($responseTimes) > 0
            ? round(array_sum($responseTimes) / count($responseTimes), 2)
            : 0;

        return response()->json([
            'data' => [
                'total_records' => $totalRecords,
                'status_counts' => $statusCounts,
                'uptime_percentage' => $uptimePercentage,
                'average_response_time' => $averageResponseTime,
                'last_check' => $lastCheck,
            ],
            'meta' => [
                'monitor_id' => $monitorId,
                'monitor_url' => $monitor->url,
            ]
        ]);
    }

    /**
     * Clean up old history records for a specific monitor
     */
    public function cleanup(Request $request, int $monitorId): JsonResponse
    {
        // Check if monitor exists and user has access
        $monitor = Monitor::findOrFail($monitorId);

        // Check authorization - only monitor owner can cleanup
        if (!auth()->check() || !$monitor->users->contains(auth()->id())) {
            abort(403, 'Access denied');
        }

        $daysToKeep = (int) $request->get('days', 30);

        if ($daysToKeep < 1 || $daysToKeep > 365) {
            return response()->json([
                'error' => 'Days must be between 1 and 365'
            ], 400);
        }

        $service = new MonitorHistoryDatabaseService();

        if (!MonitorHistory::monitorHasDatabase($monitorId)) {
            return response()->json([
                'message' => 'No database found for this monitor',
                'deleted_count' => 0
            ]);
        }

        $deletedCount = MonitorHistory::cleanupForMonitor($monitorId, $daysToKeep);

        return response()->json([
            'message' => "Cleaned up {$deletedCount} old records",
            'deleted_count' => $deletedCount,
            'days_kept' => $daysToKeep,
        ]);
    }
}
