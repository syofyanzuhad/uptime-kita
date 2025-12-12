<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorIncident;
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
        $sortBy = $request->get('sort_by', 'default'); // Default sort by id

        if ($search && mb_strlen($search) < 3) {
            $search = null;
        }

        // Validate sort option
        $validSortOptions = ['default', 'popular', 'uptime', 'response_time', 'newest', 'name', 'status'];
        if (! in_array($sortBy, $validSortOptions)) {
            $sortBy = 'default';
        }

        // Differentiate cache keys for authenticated and guest users, and also by page number
        $cacheKey = 'public_monitors_page_'.$page.'_sort_'.$sortBy;
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_'.$statusFilter;
        }
        if ($tagFilter) {
            $cacheKey .= '_tag_'.md5($tagFilter);
        }

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage, $search, $statusFilter, $tagFilter, $sortBy) {
            // Always only show public monitors
            $query = Monitor::withoutGlobalScope('user')
                ->with([
                    'users:id',
                    'uptimeDaily',
                    'tags',
                    'statistics',
                    'uptimesDaily' => function ($query) {
                        $query->where('date', '>=', now()->subDays(7)->toDateString())
                            ->orderBy('date', 'asc');
                    },
                ])
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

            // Apply sorting
            switch ($sortBy) {
                case 'popular':
                    $query->orderBy('page_views_count', 'desc');
                    break;
                case 'uptime':
                    $query->leftJoin('monitor_statistics', 'monitors.id', '=', 'monitor_statistics.monitor_id')
                        ->orderByRaw('COALESCE(monitor_statistics.uptime_24h, 0) DESC')
                        ->select('monitors.*');
                    break;
                case 'response_time':
                    $query->leftJoin('monitor_statistics', 'monitors.id', '=', 'monitor_statistics.monitor_id')
                        ->orderByRaw('COALESCE(monitor_statistics.avg_response_time_24h, 999999) ASC')
                        ->select('monitors.*');
                    break;
                case 'name':
                    $query->orderBy('url', 'asc');
                    break;
                case 'status':
                    $query->orderByRaw("CASE WHEN uptime_status = 'down' THEN 0 WHEN uptime_status = 'up' THEN 1 ELSE 2 END");
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'default':
                default:
                    $query->orderBy('id', 'asc');
                    break;
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

        // Get latest incidents for public monitors
        $latestIncidents = cache()->remember('public_monitors_latest_incidents', 300, function () {
            return MonitorIncident::with(['monitor:id,url,name,is_public'])
                ->whereHas('monitor', function ($query) {
                    $query->where('is_public', true);
                })
                ->orderBy('started_at', 'desc')
                ->limit(10)
                ->get(['id', 'monitor_id', 'type', 'started_at', 'ended_at', 'duration_minutes', 'reason', 'status_code']);
        });

        $appUrl = config('app.url');
        $upCount = Monitor::public()->where('uptime_status', 'up')->count();
        $totalPublic = Monitor::public()->count();

        return Inertia::render('monitors/PublicIndex', [
            'monitors' => $publicMonitors,
            'filters' => [
                'search' => $search,
                'status_filter' => $statusFilter,
                'tag_filter' => $tagFilter,
                'sort_by' => $sortBy,
            ],
            'availableTags' => $availableTags,
            'latestIncidents' => $latestIncidents,
            'stats' => [
                'total' => $publicMonitors->total(),
                'up' => $upCount,
                'down' => Monitor::public()->where('uptime_status', 'down')->count(),
                'total_public' => $totalPublic,
                'daily_checks' => $this->getDailyChecksCount(),
                'monthly_checks' => $this->getMonthlyChecksCount(),
            ],
        ])->withViewData([
            'ogTitle' => 'Public Monitors - Uptime Kita',
            'ogDescription' => "Monitoring {$totalPublic} public services. {$upCount} services are up and running.",
            'ogImage' => "{$appUrl}/og/monitors.png",
            'ogUrl' => "{$appUrl}/public-monitors",
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
        $sortBy = $request->get('sort_by', 'default');

        if ($search && mb_strlen($search) < 3) {
            $search = null;
        }

        // Validate sort option
        $validSortOptions = ['default', 'popular', 'uptime', 'response_time', 'newest', 'name', 'status'];
        if (! in_array($sortBy, $validSortOptions)) {
            $sortBy = 'default';
        }

        // Differentiate cache keys for authenticated and guest users, and also by page number
        $cacheKey = ($authenticated ? 'public_monitors_authenticated_'.auth()->id() : 'public_monitors_guest').'_page_'.$page.'_sort_'.$sortBy;
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_'.$statusFilter;
        }
        if ($tagFilter) {
            $cacheKey .= '_tag_'.md5($tagFilter);
        }

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage, $search, $statusFilter, $tagFilter, $sortBy) {
            // Always only show public monitors
            $query = Monitor::withoutGlobalScope('user')
                ->with([
                    'users:id',
                    'uptimeDaily',
                    'tags',
                    'statistics',
                    'uptimesDaily' => function ($query) {
                        $query->where('date', '>=', now()->subDays(7)->toDateString())
                            ->orderBy('date', 'asc');
                    },
                ])
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

            // Apply sorting
            switch ($sortBy) {
                case 'popular':
                    $query->orderBy('page_views_count', 'desc');
                    break;
                case 'uptime':
                    $query->leftJoin('monitor_statistics', 'monitors.id', '=', 'monitor_statistics.monitor_id')
                        ->orderByRaw('COALESCE(monitor_statistics.uptime_24h, 0) DESC')
                        ->select('monitors.*');
                    break;
                case 'response_time':
                    $query->leftJoin('monitor_statistics', 'monitors.id', '=', 'monitor_statistics.monitor_id')
                        ->orderByRaw('COALESCE(monitor_statistics.avg_response_time_24h, 999999) ASC')
                        ->select('monitors.*');
                    break;
                case 'name':
                    $query->orderBy('url', 'asc');
                    break;
                case 'status':
                    $query->orderByRaw("CASE WHEN uptime_status = 'down' THEN 0 WHEN uptime_status = 'up' THEN 1 ELSE 2 END");
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'default':
                default:
                    $query->orderBy('id', 'asc');
                    break;
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

    /**
     * Get the total number of checks performed this month for public monitors.
     */
    private function getMonthlyChecksCount(): int
    {
        // Cache the monthly checks count for 1 hour
        return cache()->remember('public_monitors_monthly_checks', 3600, function () {
            // First try to get from monitor_statistics table (if data exists)
            $statsCount = DB::table('monitor_statistics')
                ->join('monitors', 'monitor_statistics.monitor_id', '=', 'monitors.id')
                ->where('monitors.is_public', true)
                ->sum('monitor_statistics.total_checks_30d');

            if ($statsCount > 0) {
                return (int) $statsCount;
            }

            // Fallback to counting from monitor_histories for the current month
            return MonitorHistory::whereIn('monitor_id', function ($query) {
                $query->select('id')
                    ->from('monitors')
                    ->where('is_public', true);
            })
                ->whereMonth('checked_at', now()->month)
                ->whereYear('checked_at', now()->year)
                ->count();
        });
    }
}
