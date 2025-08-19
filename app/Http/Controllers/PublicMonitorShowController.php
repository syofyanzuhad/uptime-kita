<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorStatistic;
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
            ->with('statistics')
            ->first();

        // If monitor not found, show the not found page
        if (! $monitor) {
            return $this->showNotFound($domain);
        }

        // Try to use cached statistics first
        $statistics = $monitor->statistics;
        
        if ($statistics && $statistics->isFresh()) {
            // Use cached statistics
            $histories = $statistics->recent_history_100m ?? [];
            $uptimeStats = $statistics->uptime_stats;
            $responseTimeStats = $statistics->response_time_stats;
        } else {
            // Fallback to live calculation if no cached stats or they're stale
            $histories = $this->getLiveHistory($monitor);
            $uptimeStats = $this->calculateUptimeStats($monitor);
            $responseTimeStats = $this->getLiveResponseTimeStats($monitor, $performanceService);
        }

        // Load uptime daily data and recent incidents (these are still needed)
        $monitor->load(['uptimesDaily' => function ($query) {
            $query->orderBy('date', 'desc')->limit(90);
        }, 'recentIncidents' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        return Inertia::render('monitors/PublicShow', [
            'monitor' => new MonitorResource($monitor),
            'histories' => $histories,
            'uptimeStats' => $uptimeStats,
            'responseTimeStats' => $responseTimeStats,
            'recentIncidents' => $monitor->recentIncidents,
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
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        if ($histories->isEmpty()) {
            return 100.0;
        }

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
        
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $oneHundredMinutesAgo)
            ->orderBy('created_at', 'desc')
            ->limit(100)
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
        return Inertia::render('monitors/PublicShowNotFound', [
            'domain' => $domain,
            'suggestedUrl' => 'https://'.$domain,
        ]);
    }
}
