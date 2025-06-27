<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class PrivateMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 12; // Number of monitors per page
        $search = $request->get('search');

        $query = Monitor::whereHas('users', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->where('is_public', false)
        ->search($search)
        ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $privateMonitors = $paginator->getCollection()->map(function ($monitor) {
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
            ];
        });

        return response()->json([
            'data' => $privateMonitors->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more_pages' => $paginator->hasMorePages(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ]);
    }
}
