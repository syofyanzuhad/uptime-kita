<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicMonitorController extends Controller
{
    /**
     * Display the public monitors page.
     */
    public function index(Request $request)
    {
        // $authenticated = auth()->check();
        // Ensure page is numeric and valid
        $page = (int) $request->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        $perPage = 50; // Number of monitors per page
        $search = $request->get('search');
        $statusFilter = $request->get('status_filter', 'all');

        if ($search && mb_strlen($search) < 3) {
            $search = null;
        }

        // Differentiate cache keys for authenticated and guest users, and also by page number
        $cacheKey = 'public_monitors_page_'.$page;
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

            // Exclude pinned monitors for authenticated users
            // if (auth()->check()) {
            //     $query->whereDoesntHave('users', function ($subQuery) {
            //         $subQuery->where('user_id', auth()->id())
            //             ->where('user_monitor.is_pinned', true);
            //     });
            // }

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

        // Check if request wants JSON response (for load more functionality)
        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json($publicMonitors);
        }

        return Inertia::render('monitors/PublicIndex', [
            'monitors' => $publicMonitors,
            'filters' => [
                'search' => $search,
                'status_filter' => $statusFilter,
            ],
            'stats' => [
                'total' => $publicMonitors->total(),
                'up' => Monitor::public()->where('uptime_status', 'up')->count(),
                'down' => Monitor::public()->where('uptime_status', 'down')->count(),
                'total_public' => Monitor::public()->count(),
            ],
        ]);
    }

    /**
     * Handle the incoming request for JSON API.
     */
    public function __invoke(Request $request)
    {
        $authenticated = auth()->check();
        // Ensure page is numeric and valid
        $page = (int) $request->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }
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

            // Exclude pinned monitors for authenticated users
            if (auth()->check()) {
                $query->whereDoesntHave('users', function ($subQuery) {
                    $subQuery->where('user_id', auth()->id())
                        ->where('user_monitor.is_pinned', true);
                });
            }

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
