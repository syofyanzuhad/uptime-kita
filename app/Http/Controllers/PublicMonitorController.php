<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $perPage = min((int) $request->get('per_page', 15), 100); // Max 100 monitors per page, default 15
        $search = $request->get('search');
        $statusFilter = $request->get('status_filter', 'all');
        $tagFilter = $request->get('tag_filter');

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
        if ($tagFilter) {
            $cacheKey .= '_tag_'.md5($tagFilter);
        }

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage, $search, $statusFilter, $tagFilter) {
            // Always only show public monitors
            $query = Monitor::withoutGlobalScope('user')
                ->with(['users:id', 'uptimeDaily', 'tags'])
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

            // Apply tag filter
            if ($tagFilter) {
                $query->withAnyTags([$tagFilter]);
            }

            return new MonitorCollection(
                $query->paginate($perPage, ['*'], 'page', $page)
            );
        });

        // Check if request wants JSON response (for load more functionality)
        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json($publicMonitors);
        }

        // Get all unique tags used in public monitors
        $availableTags = \Spatie\Tags\Tag::whereIn('id', function ($query) {
            $query->select('tag_id')
                ->from('taggables')
                ->where('taggable_type', 'App\Models\Monitor')
                ->whereIn('taggable_id', function ($subQuery) {
                    $subQuery->select('id')
                        ->from('monitors')
                        ->where('is_public', true);
                });
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('monitors/PublicIndex', [
            'monitors' => $publicMonitors,
            'filters' => [
                'search' => $search,
                'status_filter' => $statusFilter,
                'tag_filter' => $tagFilter,
            ],
            'availableTags' => $availableTags,
            'stats' => [
                'total' => $publicMonitors->total(),
                'up' => Monitor::public()->where('uptime_status', 'up')->count(),
                'down' => Monitor::public()->where('uptime_status', 'down')->count(),
                'total_public' => Monitor::public()->count(),
                'daily_checks' => $this->getDailyChecksCount(),
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
        $perPage = min((int) $request->get('per_page', 15), 100); // Max 100 monitors per page, default 15
        $search = $request->get('search');
        $statusFilter = $request->get('status_filter', 'all');
        $tagFilter = $request->get('tag_filter');

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
        if ($tagFilter) {
            $cacheKey .= '_tag_'.md5($tagFilter);
        }

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage, $search, $statusFilter, $tagFilter) {
            // Always only show public monitors
            $query = Monitor::withoutGlobalScope('user')
                ->with(['users:id', 'uptimeDaily', 'tags'])
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

            // Apply tag filter
            if ($tagFilter) {
                $query->withAnyTags([$tagFilter]);
            }

            return new MonitorCollection(
                $query->paginate($perPage, ['*'], 'page', $page)
            );
        });

        return response()->json($publicMonitors);
    }

    /**
     * Get the total number of checks performed today for public monitors.
     */
    private function getDailyChecksCount(): int
    {
        // Cache the daily checks count for 15 minutes
        return cache()->remember('public_monitors_daily_checks', 900, function () {
            // First try to get from monitor_statistics table (if data exists)
            $statsCount = DB::table('monitor_statistics')
                ->join('monitors', 'monitor_statistics.monitor_id', '=', 'monitors.id')
                ->where('monitors.is_public', true)
                ->sum('monitor_statistics.total_checks_24h');

            if ($statsCount > 0) {
                return (int) $statsCount;
            }

            // Fallback to counting from monitor_histories for today
            return MonitorHistory::whereIn('monitor_id', function ($query) {
                $query->select('id')
                    ->from('monitors')
                    ->where('is_public', true);
            })
                ->whereDate('checked_at', today())
                ->count();
        });
    }
}
