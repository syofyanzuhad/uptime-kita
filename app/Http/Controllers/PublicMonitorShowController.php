<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
use App\Jobs\IncrementMonitorPageViewJob;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Services\MonitorPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicMonitorShowController extends Controller
{
    /**
     * Display the public monitor page.
     */
    public function show(Request $request, MonitorPerformanceService $performanceService): Response
    {
        // Get the domain from the request
        $domain = urldecode($request->route('domain'));

        // Build the full HTTPS URL
        $url = 'https://'.$domain;

        // Find the monitor by URL with its statistics
        $monitor = Monitor::where('url', $url)
            ->where('is_public', true)
            ->where('uptime_check_enabled', true)
            ->with(['statistics', 'tags'])
            ->first();

        // If monitor not found, show the not found page
        if (! $monitor) {
            return $this->showNotFound($domain);
        }

        // Dispatch job to increment page view count (non-blocking)
        IncrementMonitorPageViewJob::dispatch($monitor->id, $request->ip());

        // Use real-time data with short cache like private monitor show
        $histories = cache()->remember("public_monitor_{$monitor->id}_histories", 60, function () use ($monitor) {
            return $this->getLiveHistory($monitor);
        });

        $uptimeStats = cache()->remember("public_monitor_{$monitor->id}_uptime_stats", 60, function () use ($monitor) {
            return $this->calculateUptimeStats($monitor);
        });

        $responseTimeStats = cache()->remember("public_monitor_{$monitor->id}_response_stats", 60, function () use ($monitor, $performanceService) {
            return $this->getLiveResponseTimeStats($monitor, $performanceService);
        });

        // Load uptime daily data and latest incidents (these are still needed)
        $monitor->load(['uptimesDaily' => function ($query) {
            $query->orderBy('date', 'desc')->limit(90);
        }, 'latestIncidents' => function ($query) {
            $query->orderBy('started_at', 'desc')->limit(10);
        }]);

        $appUrl = config('app.url');
        $monitorName = $monitor->name ?? $domain;
        $uptimePercent = $uptimeStats['24h'] ?? 0;
        $statusText = $monitor->uptime_status === 'up' ? 'Operational' : 'Down';

        return Inertia::render('monitors/PublicShow', [
            'monitor' => new MonitorResource($monitor),
            'histories' => $histories,
            'uptimeStats' => $uptimeStats,
            'responseTimeStats' => $responseTimeStats,
            'latestIncidents' => $monitor->latestIncidents,
        ])->withViewData([
            'ogTitle' => "{$monitorName} Status - Uptime Kita",
            'ogDescription' => "{$statusText} - {$uptimePercent}% uptime in the last 24 hours. Monitor real-time status and performance.",
            'ogImage' => "{$appUrl}/og/monitor/{$domain}.png",
            'ogUrl' => "{$appUrl}/m/{$domain}",
        ]);
    }

    /**
     * Calculate uptime statistics for different periods.
     */
    private function calculateUptimeStats($monitor): array
    {
        $now = now();

        return [
            '24h' => $this->calculateUptimePercentage($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(30)),
            '90d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(90)),
        ];
    }

    /**
     * Calculate uptime percentage for a specific period.
     */
    private function calculateUptimePercentage($monitor, $startDate): float
    {
        // Get unique history IDs using raw SQL to ensure only one record per minute
        $sql = "
            SELECT id FROM (
                SELECT id, created_at, ROW_NUMBER() OVER (
                    PARTITION BY monitor_id, strftime('%Y-%m-%d %H:%M', created_at) 
                    ORDER BY created_at DESC, id DESC
                ) as rn
                FROM monitor_histories
                WHERE monitor_id = ?
                AND created_at >= ?
            ) ranked
            WHERE rn = 1
        ";

        $uniqueIds = \DB::select($sql, [$monitor->id, $startDate]);
        $ids = array_column($uniqueIds, 'id');

        if (empty($ids)) {
            return 100.0;
        }

        // Get unique histories
        $histories = MonitorHistory::whereIn('id', $ids)->get();

        $upCount = $histories->where('uptime_status', 'up')->count();
        $totalCount = $histories->count();

        return round(($upCount / $totalCount) * 100, 2);
    }

    /**
     * Get live history data when cached version is not available
     */
    private function getLiveHistory($monitor): array
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

        $uniqueIds = \DB::select($sql, [$monitor->id]);
        $ids = array_column($uniqueIds, 'id');

        // Get unique histories and filter by time
        $histories = MonitorHistory::whereIn('id', $ids)
            ->where('created_at', '>=', $oneHundredMinutesAgo)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform to match the cached format
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
     * Get live response time statistics when cached version is not available
     */
    private function getLiveResponseTimeStats($monitor, MonitorPerformanceService $performanceService): array
    {
        $responseTimeStats = $performanceService->getResponseTimeStats(
            $monitor->id,
            Carbon::now()->subDay(),
            Carbon::now()
        );

        return [
            'average' => $responseTimeStats['avg'],
            'min' => $responseTimeStats['min'],
            'max' => $responseTimeStats['max'],
        ];
    }

    /**
     * Display the not found page for monitors.
     */
    private function showNotFound(string $domain): Response
    {
        $appUrl = config('app.url');

        return Inertia::render('monitors/PublicShowNotFound', [
            'domain' => $domain,
            'suggestedUrl' => 'https://'.$domain,
        ])->withViewData([
            'ogTitle' => 'Monitor Not Found - Uptime Kita',
            'ogDescription' => "The monitor for {$domain} was not found or is not public.",
            'ogImage' => "{$appUrl}/og/monitors.png",
            'ogUrl' => "{$appUrl}/m/{$domain}",
        ]);
    }
}
