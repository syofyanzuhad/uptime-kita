<?php

namespace App\Http\Controllers;

use App\Http\Resources\SimpleMonitorResource;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Tags\Tag;

class MonitorCompactController extends Controller
{
    /**
     * Display a compact listing of all monitors.
     */
    public function index(Request $request)
    {
        // Increase memory limit for this request to handle wallboard data
        if (config('app.env') !== 'local') {
            ini_set('memory_limit', '1024M');
        }

        // Search filter applied to count query too
        $search = $request->search;
        
        $countQuery = Monitor::query();
        if ($search) {
            $countQuery->search($search);
        }
        
        if (! auth()->check()) {
            $countQuery->public();
        }

        $totalMonitors = $countQuery->count();

        $query = Monitor::query()
            ->select([
                'id',
                'url',
                'uptime_status',
                'uptime_check_enabled',
                'uptime_last_check_date',
                'created_at',
                'updated_at',
            ])
            ->with(['tags', 'uptimeDaily', 'statistics', 'latestHistory']);

        if ($search) {
            $query->search($search);
        }

        if (! auth()->check()) {
            $query->public();
        }

        // Use simplePaginate to avoid the heavy count(*) query on every page
        $monitors = $query->orderBy('url')->simplePaginate(500)->withQueryString();

        // Prevent expensive appends during transformation
        $monitors->getCollection()->each(function ($monitor) {
            $monitor->setAppends([]);
        });

        $monitorIds = collect($monitors->items())->pluck('id');

        $availableTags = Tag::whereIn('id', function ($query) use ($monitorIds) {
            $query->select('tag_id')
                ->from('taggables')
                ->whereIn('taggable_id', $monitorIds)
                ->where('taggable_type', Monitor::class);
        })->get(['id', 'name']);

        return Inertia::render('monitors/Compact', [
            'monitors' => SimpleMonitorResource::collection($monitors),
            'pagination' => [
                'current_page' => $monitors->currentPage(),
                'prev_page_url' => $monitors->previousPageUrl(),
                'next_page_url' => $monitors->nextPageUrl(),
                'per_page' => $monitors->perPage(),
                'total' => $totalMonitors,
                'from' => $monitors->firstItem(),
                'to' => $monitors->lastItem(),
            ],
            'availableTags' => $availableTags,
            'totalCount' => $totalMonitors,
        ]);
    }
}
