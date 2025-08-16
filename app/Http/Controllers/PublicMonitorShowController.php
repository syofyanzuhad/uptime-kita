<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicMonitorShowController extends Controller
{
    /**
     * Display the public monitor page.
     */
    public function show(Request $request): Response
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

        // Load recent history (last 30 days)
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', now()->subDays(30))
            ->orderBy('checked_at', 'desc')
            ->limit(1000)
            ->get();

        // Load uptime daily data
        $monitor->load(['uptimeDaily' => function ($query) {
            $query->orderBy('date', 'desc')->limit(90);
        }]);

        // Calculate uptime percentages
        $uptimeStats = $this->calculateUptimeStats($monitor);

        return Inertia::render('monitors/PublicShow', [
            'monitor' => new MonitorResource($monitor),
            'histories' => $histories,
            'uptimeStats' => $uptimeStats,
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
            ->where('checked_at', '>=', $startDate)
            ->get();

        if ($histories->isEmpty()) {
            return 100.0;
        }

        $upCount = $histories->where('status', 'up')->count();
        $totalCount = $histories->count();

        return round(($upCount / $totalCount) * 100, 2);
    }
}
