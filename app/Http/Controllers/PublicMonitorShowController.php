<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
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

        // Find the monitor by URL
        $monitor = Monitor::where('url', $url)
            ->where('is_public', true)
            ->where('uptime_check_enabled', true)
            ->firstOrFail();

        // Load recent history (last 30 days) - using created_at
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get();

        // Load uptime daily data with response time metrics
        $monitor->load(['uptimesDaily' => function ($query) {
            $query->orderBy('date', 'desc')->limit(90);
        }, 'recentIncidents' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(1000);
        }]);

        // Calculate uptime percentages
        $uptimeStats = $this->calculateUptimeStats($monitor);

        // Get response time statistics for last 24 hours
        $responseTimeStats = $performanceService->getResponseTimeStats(
            $monitor->id,
            Carbon::now()->subDay(),
            Carbon::now()
        );

        return Inertia::render('monitors/PublicShow', [
            'monitor' => new MonitorResource($monitor),
            'histories' => $histories,
            'uptimeStats' => $uptimeStats,
            'responseTimeStats' => [
                'average' => $responseTimeStats['avg'],
                'min' => $responseTimeStats['min'],
                'max' => $responseTimeStats['max'],
            ],
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
}
