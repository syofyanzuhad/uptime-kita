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
            ini_set('memory_limit', '512M');
        }

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

        // If not logged in, only show public monitors
        if (! auth()->check()) {
            $query->public();
        }

        $monitors = $query->orderBy('url')->get();

        $monitorIds = $monitors->pluck('id');

        $availableTags = Tag::whereIn('id', function ($query) use ($monitorIds) {
            $query->select('tag_id')
                ->from('taggables')
                ->whereIn('taggable_id', $monitorIds)
                ->where('taggable_type', Monitor::class);
        })->get(['id', 'name']);

        return Inertia::render('monitors/Compact', [
            'monitors' => SimpleMonitorResource::collection($monitors),
            'availableTags' => $availableTags,
        ]);
    }
}
