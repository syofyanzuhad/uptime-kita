<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
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
        $perPage = 50; // Number of monitors per page
        $search = $request->get('search');
        $statusFilter = $request->get('status_filter', 'all');

        if ($search && mb_strlen($search) < 3) {
            $search = null;
        }

        // Differentiate cache keys for authenticated and guest users, and also by page number
        $cacheKey = ($authenticated ? 'public_monitors_authenticated_'.auth()->id() : 'public_monitors_guest').'_page_'.$page;
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_'.$statusFilter;
        }

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage, $search, $statusFilter) {
            // Always only show public monitors
            $query = Monitor::withoutGlobalScope('user')
                ->with(['users:id', 'uptimeDaily'])
                ->public();

            // Apply status filter
            if ($statusFilter === 'up' || $statusFilter === 'down') {
                $query->where('uptime_status', $statusFilter);
            } elseif ($statusFilter === 'disabled' || $statusFilter === 'globally_disabled') {
                $query->withoutGlobalScope('enabled')->where('uptime_check_enabled', false);
            } elseif ($statusFilter === 'globally_enabled') {
                $query->withoutGlobalScope('enabled')->where('uptime_check_enabled', true);
            } elseif ($statusFilter === 'unsubscribed') {
                $query->whereDoesntHave('users', function ($query) {
                    $query->where('user_id', auth()->id());
                });
            }

            if ($search) {
                $query->search($search);
            }

            return new MonitorCollection(
                $query->paginate($perPage, ['*'], 'page', $page)
            );
        });

        return response()->json($publicMonitors);
    }
}
