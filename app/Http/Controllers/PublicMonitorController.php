<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorIncident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Tags\Tag;

class PublicMonitorController extends Controller
{
    private const VALID_SORTS = ['default', 'popular', 'uptime', 'response_time', 'newest', 'name', 'status'];

    /**
     * Display the public monitors page (Inertia).
     */
    public function index(Request $request): Response|JsonResponse
    {
        $filters = $this->getFilters($request);
        $cacheKey = $this->cacheKey($filters, 'inertia');

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($filters) {
            return new MonitorCollection(
                $this->buildQuery($filters)->paginate($filters['perPage'], ['*'], 'page', $filters['page'])
            );
        });

        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            return response()->json($publicMonitors);
        }

        $availableTags = Tag::whereIn('id', function ($query) {
            $query->select('tag_id')
                ->from('taggables')
                ->where('taggable_type', 'App\Models\Monitor')
                ->whereIn('taggable_id', function ($subQuery) {
                    $subQuery->select('id')->from('monitors')->where('is_public', true);
                });
        })->orderBy('name')->get(['id', 'name']);

        $latestIncidents = cache()->remember('public_monitors_latest_incidents', 300, function () {
            return MonitorIncident::with(['monitor:id,url,display_name,is_public'])
                ->whereHas('monitor', function ($query) {
                    $query->where('is_public', true);
                })
                ->orderBy('started_at', 'desc')
                ->limit(10)
                ->get(['id', 'monitor_id', 'type', 'started_at', 'ended_at', 'duration_minutes', 'reason', 'status_code']);
        });

        $appUrl = config('app.url');
        $upCount = Monitor::withoutGlobalScope('user')->public()->where('uptime_status', 'up')->count();
        $totalPublic = Monitor::withoutGlobalScope('user')->public()->count();

        return Inertia::render('monitors/PublicIndex', [
            'monitors' => $publicMonitors,
            'filters' => [
                'search' => $filters['search'],
                'status_filter' => $filters['statusFilter'],
                'tag_filter' => $filters['tagFilter'],
                'sort_by' => $filters['sortBy'],
            ],
            'availableTags' => $availableTags,
            'latestIncidents' => $latestIncidents,
            'stats' => [
                'total' => $publicMonitors->total(),
                'up' => $upCount,
                'down' => Monitor::withoutGlobalScope('user')->public()->where('uptime_status', 'down')->count(),
                'total_public' => $totalPublic,
                'daily_checks' => $this->getDailyChecksCount(),
                'monthly_checks' => $this->getMonthlyChecksCount(),
            ],
            'showSmolLaunchBadge' => config('app.show_smol_launch_badge'),
            'appUrl' => $appUrl,
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
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);
        $cacheKey = $this->cacheKey($filters, 'api');

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($filters) {
            return new MonitorCollection(
                $this->buildQuery($filters, true)->paginate($filters['perPage'], ['*'], 'page', $filters['page'])
            );
        });

        return response()->json($publicMonitors);
    }

    private function getFilters(Request $request): array
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min((int) $request->get('per_page', 15), 100);
        $search = $request->get('search');
        if ($search && mb_strlen($search) < 3) {
            $search = null;
        }
        $statusFilter = $request->get('status_filter', 'all');
        $tagFilter = $request->get('tag_filter');
        $sortBy = $request->get('sort_by', 'default');
        if (! in_array($sortBy, self::VALID_SORTS, true)) {
            $sortBy = 'default';
        }

        return compact('page', 'perPage', 'search', 'statusFilter', 'tagFilter', 'sortBy');
    }

    private function buildQuery(array $filters, bool $excludePinned = false): Builder
    {
        $query = Monitor::withoutGlobalScope('user')
            ->with([
                'users:id',
                'uptimeDaily',
                'tags',
                'statistics',
                'uptimesDaily' => function ($q) {
                    $q->where('date', '>=', now()->subDays(7)->toDateString())->orderBy('date', 'asc');
                },
            ])
            ->public();

        if ($excludePinned && auth()->check()) {
            $query->whereDoesntHave('users', function ($subQuery) {
                $subQuery->where('user_id', auth()->id())->where('user_monitor.is_pinned', true);
            });
        }

        if ($filters['statusFilter'] === 'up' || $filters['statusFilter'] === 'down') {
            $query->where('uptime_status', $filters['statusFilter']);
        } elseif ($filters['statusFilter'] === 'disabled' || $filters['statusFilter'] === 'globally_disabled') {
            $query->withoutGlobalScope('enabled')->where('uptime_check_enabled', false);
        } elseif ($filters['statusFilter'] === 'globally_enabled') {
            $query->withoutGlobalScope('enabled')->where('uptime_check_enabled', true);
        } elseif ($filters['statusFilter'] === 'unsubscribed') {
            $query->whereDoesntHave('users', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        if ($filters['search']) {
            $query->search($filters['search']);
        }

        if ($filters['tagFilter']) {
            $query->withAnyTags([$filters['tagFilter']]);
        }

        switch ($filters['sortBy']) {
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
            default:
                $query->orderBy('id', 'asc');
                break;
        }

        return $query;
    }

    private function cacheKey(array $filters, string $prefix): string
    {
        $authPart = auth()->check() ? 'auth_'.auth()->id() : 'guest';
        $key = "public_monitors_{$prefix}_{$authPart}_page_{$filters['page']}_sort_{$filters['sortBy']}";
        if ($filters['search']) {
            $key .= '_search_'.md5($filters['search']);
        }
        if ($filters['statusFilter'] !== 'all') {
            $key .= '_filter_'.$filters['statusFilter'];
        }
        if ($filters['tagFilter']) {
            $key .= '_tag_'.md5($filters['tagFilter']);
        }

        return $key;
    }

    /**
     * Get the total number of checks performed today for public monitors.
     */
    private function getDailyChecksCount(): int
    {
        return cache()->remember('public_monitors_daily_checks', 900, function () {
            $statsCount = DB::table('monitor_statistics')
                ->join('monitors', 'monitor_statistics.monitor_id', '=', 'monitors.id')
                ->where('monitors.is_public', true)
                ->sum('monitor_statistics.total_checks_24h');

            if ($statsCount > 0) {
                return (int) $statsCount;
            }

            return MonitorHistory::whereIn('monitor_id', function ($query) {
                $query->select('id')->from('monitors')->where('is_public', true);
            })->where('checked_at', '>=', today())->count();
        });
    }

    /**
     * Get the total number of checks performed this month for public monitors.
     */
    private function getMonthlyChecksCount(): int
    {
        return cache()->remember('public_monitors_monthly_checks', 3600, function () {
            $statsCount = DB::table('monitor_statistics')
                ->join('monitors', 'monitor_statistics.monitor_id', '=', 'monitors.id')
                ->where('monitors.is_public', true)
                ->sum('monitor_statistics.total_checks_30d');

            if ($statsCount > 0) {
                return (int) $statsCount;
            }

            return MonitorHistory::whereIn('monitor_id', function ($query) {
                $query->select('id')->from('monitors')->where('is_public', true);
            })->where('checked_at', '>=', now()->startOfMonth())->count();
        });
    }
}
