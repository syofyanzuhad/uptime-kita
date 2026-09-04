<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorIncident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicIncidentController extends Controller
{
    /**
     * Display the public incidents history page.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $status = $request->input('status', 'all');
        $range = $request->input('range', '30d');

        $query = MonitorIncident::query()
            ->with(['monitor:id,url,display_name,is_public,uptime_status,raw_url'])
            ->whereHas('monitor', function ($q) {
                $q->where('is_public', true);
            });

        // Search filter
        if (! empty($search)) {
            $query->whereHas('monitor', function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('raw_url', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status === 'ongoing') {
            $query->whereNull('ended_at');
        } elseif ($status === 'resolved') {
            $query->whereNotNull('ended_at');
        }

        // Time range filter
        if ($range === '7d') {
            $query->where('started_at', '>=', now()->subDays(7));
        } elseif ($range === '30d') {
            $query->where('started_at', '>=', now()->subDays(30));
        } elseif ($range === '90d') {
            $query->where('started_at', '>=', now()->subDays(90));
        }

        $incidents = $query->orderBy('started_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Calculate summary statistics
        $publicBase = MonitorIncident::query()->whereHas('monitor', fn ($q) => $q->where('is_public', true));
        $ongoingCount = (clone $publicBase)->whereNull('ended_at')->count();
        $resolved30d = (clone $publicBase)->whereNotNull('ended_at')->where('ended_at', '>=', now()->subDays(30))->count();
        $avgDuration = (clone $publicBase)->whereNotNull('duration_minutes')->where('started_at', '>=', now()->subDays(30))->avg('duration_minutes');
        $totalPublicMonitors = Monitor::withoutGlobalScope('user')->public()->count();

        $appUrl = config('app.url');

        return Inertia::render('incidents/PublicIndex', [
            'incidents' => $incidents,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'range' => $range,
            ],
            'stats' => [
                'ongoing_count' => $ongoingCount,
                'resolved_30d' => $resolved30d,
                'avg_duration_minutes' => $avgDuration ? (int) round($avgDuration) : 0,
                'total_public_monitors' => $totalPublicMonitors,
            ],
            'appUrl' => $appUrl,
        ])->withViewData([
            'ogTitle' => 'Incident History - Uptime Kita',
            'ogDescription' => "Real-time and historical incident reports across {$totalPublicMonitors} public monitored services.",
            'ogImage' => "{$appUrl}/og/monitors.png",
            'ogUrl' => "{$appUrl}/incidents",
        ]);
    }
}
