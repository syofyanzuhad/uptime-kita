<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use Illuminate\Http\Request;

class PrivateMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter', 'all');

        // Build cache key based on search query and status filter
        $cacheKey = 'private_monitors_page_'.auth()->id().'_'.$page;
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_'.$statusFilter;
        }

        $monitors = cache()->remember($cacheKey, 60, function () use ($search, $statusFilter) {
            if ($statusFilter === 'disabled') {
                // User-specific disabled: monitors where user has is_active = false
                $query = Monitor::withoutGlobalScope('user')->private()
                    ->with(['users:id', 'uptimeDaily'])
                    ->whereHas('users', function ($query) {
                        $query->where('user_id', auth()->id());
                        $query->where('user_monitor.is_active', false);
                    })
                    ->orderBy('created_at', 'desc');
            } elseif ($statusFilter === 'globally_enabled') {
                $query = Monitor::withoutGlobalScope('user')->withoutGlobalScope('enabled')->private()
                    ->with(['users:id', 'uptimeDaily'])
                    ->where('uptime_check_enabled', true)
                    ->whereHas('users', function ($query) {
                        $query->where('user_id', auth()->id());
                    })
                    ->orderBy('created_at', 'desc');
            } elseif ($statusFilter === 'globally_disabled') {
                $query = Monitor::withoutGlobalScope('user')->withoutGlobalScope('enabled')->private()
                    ->with(['users:id', 'uptimeDaily'])
                    ->where('uptime_check_enabled', false)
                    ->whereHas('users', function ($query) {
                        $query->where('user_id', auth()->id());
                    })
                    ->orderBy('created_at', 'desc');
            } else {
                $query = Monitor::private()
                    ->with(['users:id', 'uptimeDaily'])
                    ->orderBy('created_at', 'desc');
                if ($statusFilter === 'up' || $statusFilter === 'down') {
                    $query->where('uptime_status', $statusFilter);
                }
            }

            // Apply search filter if provided
            if ($search && strlen($search) >= 3) {
                $query->search($search);
            }

            return new MonitorCollection($query->paginate(12));
        });

        return response()->json($monitors);
    }
}
