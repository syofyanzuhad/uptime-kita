<?php

namespace App\Http\Controllers;

use App\Http\Resources\SimpleMonitorResource;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Tags\Tag;

class MonitorCompactController extends Controller
{
    /**
     * Display a compact listing of all monitors.
     */
    public function index(Request $request)
    {
        // Safety net for large datasets
        if (config('app.env') !== 'local') {
            ini_set('memory_limit', '1024M');
        }

        $search = $request->search;
        $isGuest = ! auth()->check();

        // 1. Get total count - Use highly optimized raw query for the 'no search' case
        if (! $search) {
            $totalCount = DB::table('monitors')
                ->where('uptime_check_enabled', 1)
                ->when($isGuest, fn($q) => $q->where('is_public', 1))
                ->count();
        } else {
            $totalCount = Monitor::query()
                ->when($isGuest, fn($q) => $q->public())
                ->search($search)
                ->count();
        }

        // 2. Fetch ONLY IDs for the current page (Very fast)
        $paginator = Monitor::query()
            ->select('id')
            ->when($isGuest, fn($q) => $q->public())
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('url')
            ->simplePaginate(100)
            ->withQueryString();

        $ids = collect($paginator->items())->pluck('id');

        if ($ids->isEmpty()) {
            return Inertia::render('monitors/Compact', [
                'monitors' => ['data' => []],
                'pagination' => $this->getPaginationData($paginator, $totalCount),
                'availableTags' => [],
                'totalCount' => $totalCount,
            ]);
        }

        // 3. Optimized Data Fetching (Avoid N+1 and slow SQLite strftime)
        $monitors = Monitor::query()
            ->whereIn('id', $ids)
            ->with(['tags', 'statistics']) // Fast relations
            ->get();

        // Fetch Today's Uptime manually using indexed range query instead of whereDate/strftime
        $today = now()->toDateString();
        $uptimes = MonitorUptimeDaily::whereIn('monitor_id', $ids)
            ->whereBetween('date', [$today . ' 00:00:00', $today . ' 23:59:59'])
            ->get()
            ->keyBy('monitor_id');

        // Fetch Latest History manually using a more efficient query pattern
        // We fetch the latest history ID for each monitor first
        $latestHistoryIds = MonitorHistory::whereIn('monitor_id', $ids)
            ->select(DB::raw('max(id) as id'))
            ->groupBy('monitor_id')
            ->pluck('id');
            
        $histories = MonitorHistory::whereIn('id', $latestHistoryIds)
            ->get()
            ->keyBy('monitor_id');

        // Map the manual data back to the monitors
        foreach ($monitors as $monitor) {
            $monitor->setRelation('uptimeDaily', $uptimes->get($monitor->id));
            $monitor->setRelation('latestHistory', $histories->get($monitor->id));
            // Ensure no expensive appends are triggered
            $monitor->setAppends([]);
        }

        // Maintain order from paginator
        $monitors = $monitors->sortBy(fn($m) => $ids->search($m->id))->values();

        // 4. Fetch available tags for this page
        $availableTags = Tag::whereIn('id', function ($query) use ($ids) {
            $query->select('tag_id')
                ->from('taggables')
                ->whereIn('taggable_id', $ids)
                ->where('taggable_type', Monitor::class);
        })->get(['id', 'name']);

        return Inertia::render('monitors/Compact', [
            'monitors' => SimpleMonitorResource::collection($monitors),
            'pagination' => $this->getPaginationData($paginator, $totalCount),
            'availableTags' => $availableTags,
            'totalCount' => $totalCount,
        ]);
    }

    private function getPaginationData($paginator, $total)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
            'per_page' => $paginator->perPage(),
            'total' => $total,
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
