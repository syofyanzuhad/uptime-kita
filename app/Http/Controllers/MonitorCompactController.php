<?php

namespace App\Http\Controllers;

use App\Http\Resources\SimpleMonitorResource;
use App\Models\Monitor;
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
        // Ultimate safety net for huge datasets
        if (config('app.env') !== 'local') {
            ini_set('memory_limit', '1536M');
        }

        $search = $request->search;
        $isGuest = ! auth()->check();

        // 1. Get total count using raw query to avoid Eloquent overhead entirely
        $totalCount = DB::table('monitors')
            ->where('uptime_check_enabled', 1)
            ->when($isGuest, fn($q) => $q->where('is_public', 1))
            ->when($search, function($q) use ($search) {
                $q->where(fn($sq) => $sq->where('url', 'like', "%$search%")->orWhere('name', 'like', "%$search%"));
            })
            ->count();

        // 2. Fetch ONLY the IDs for the current page (Very memory efficient)
        $paginator = Monitor::query()
            ->select('id')
            ->when($isGuest, fn($q) => $q->public())
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('url')
            ->simplePaginate(100) // Reduced page size for guaranteed stability
            ->withQueryString();

        $ids = collect($paginator->items())->pluck('id');

        // 3. Explicitly load full models and relations ONLY for these 100 IDs
        // This completely bypasses the "54k IDs in eager load" problem
        $monitors = Monitor::query()
            ->whereIn('id', $ids)
            ->with(['tags', 'uptimeDaily', 'statistics', 'latestHistory'])
            ->get()
            ->sortBy(function($m) use ($ids) {
                // Maintain the URL order from the paginator
                return $ids->indexOf($m->id);
            })
            ->values();

        // 4. Fetch available tags ONLY for the monitors on this page
        $availableTags = Tag::whereIn('id', function ($query) use ($ids) {
            $query->select('tag_id')
                ->from('taggables')
                ->whereIn('taggable_id', $ids)
                ->where('taggable_type', Monitor::class);
        })->get(['id', 'name']);

        return Inertia::render('monitors/Compact', [
            'monitors' => SimpleMonitorResource::collection($monitors),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'next_page_url' => $paginator->nextPageUrl(),
                'per_page' => $paginator->perPage(),
                'total' => $totalCount,
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'availableTags' => $availableTags,
            'totalCount' => $totalCount,
        ]);
    }
}
