<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class PublicMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $authenticated = auth()->check();
        $page = $request->get('page', 1);
        $perPage = 12; // Number of monitors per page

        // Use cache to store public monitors for authenticated and guest users
        // Differentiate cache keys for authenticated and guest users
        $cacheKey = $authenticated ? 'public_monitors_authenticated_' . auth()->id() : 'public_monitors_guest';

        $publicMonitors = cache()->remember($cacheKey, 60, function () {
            // Always only show public monitors
            return Monitor::withoutGlobalScope('user')
                ->with('users')
                ->where('is_public', true)
                ->get()
                ->map(function ($monitor) {
                    return [
                        'id' => $monitor->id,
                        'url' => $monitor->raw_url,
                        'uptime_status' => $monitor->uptime_status,
                        'last_check_date' => $monitor->uptime_last_check_date,
                        'certificate_check_enabled' => (bool) $monitor->certificate_check_enabled,
                        'certificate_status' => $monitor->certificate_status,
                        'certificate_expiration_date' => $monitor->certificate_expiration_date,
                        'down_for_events_count' => $monitor->down_for_events_count,
                        'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
                        'is_subscribed' => $monitor->is_subscribed,
                        'is_public' => $monitor->is_public,
                    ];
                });
        });

        // Apply pagination manually since we're using cache
        $total = $publicMonitors->count();
        $offset = ($page - 1) * $perPage;
        $paginatedMonitors = $publicMonitors->slice($offset, $perPage);

        $hasMorePages = ($offset + $perPage) < $total;
        $currentPage = (int) $page;
        $lastPage = ceil($total / $perPage);

        return response()->json([
            'data' => $paginatedMonitors->values(),
            'pagination' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'has_more_pages' => $hasMorePages,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ]
        ]);
    }
}
