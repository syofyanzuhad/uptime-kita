<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MonitorCompactController extends Controller
{
    /**
     * Display a compact listing of all monitors.
     */
    public function index(Request $request)
    {
        // Ultimate safety net for huge datasets - 2GB for 54k records
        if (config('app.env') !== 'local') {
            ini_set('memory_limit', '2048M');
        }

        $search = $request->search;
        $isGuest = ! auth()->check();

        // 1. Fetch RAW data from DB (No Eloquent models = massive memory savings)
        $monitors = DB::table('monitors')
            ->select([
                'id',
                'url',
                'uptime_status',
                'uptime_check_enabled',
                'uptime_last_check_date',
            ])
            ->where('uptime_check_enabled', 1)
            ->when($isGuest, fn($q) => $q->where('is_public', 1))
            ->when($search, function($q) use ($search) {
                $q->where(fn($sq) => $sq->where('url', 'like', "%$search%")->orWhere('name', 'like', "%$search%"));
            })
            ->orderBy('url')
            ->get();

        if ($monitors->isEmpty()) {
            return Inertia::render('monitors/Compact', [
                'monitors' => ['data' => []],
                'availableTags' => [],
            ]);
        }

        $ids = $monitors->pluck('id')->toArray();

        // 2. Fetch Related Data in bulk
        
        // Today's Uptime (Optimized simple date match)
        // Fixed: Use simple string date matching which is more reliable for SQLite DATE columns
        $today = now()->toDateString();
        $uptimes = DB::table('monitor_uptime_dailies')
            ->whereIn('monitor_id', $ids)
            ->where('date', $today)
            ->get()
            ->keyBy('monitor_id');

        // Latest Statistics (Only 24h)
        $stats = DB::table('monitor_statistics')
            ->whereIn('monitor_id', $ids)
            ->select(['monitor_id', 'uptime_24h', 'avg_response_time_24h', 'incidents_24h'])
            ->get()
            ->keyBy('monitor_id');

        // Tags (Direct many-to-many raw fetch)
        $allTags = DB::table('tags')
            ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
            ->whereIn('taggables.taggable_id', $ids)
            ->where('taggables.taggable_type', Monitor::class)
            ->select(['tags.id', 'tags.name', 'taggables.taggable_id'])
            ->get()
            ->groupBy('taggable_id');

        // Available tags for the sidebar/filter
        $availableTags = $allTags->flatten(1)->unique('id')->values()->map(function($t) {
            return ['id' => $t->id, 'name' => $t->name, 'color' => null];
        });

        // 3. Assemble the JSON payload manually (Bypassing API resources)
        $data = $monitors->map(function ($m) use ($uptimes, $stats, $allTags) {
            $host = parse_url($m->url, PHP_URL_HOST) ?? $m->url;
            $host = str_replace('www.', '', $host);
            
            $monitorUptime = $uptimes->get($m->id);
            $monitorStats = $stats->get($m->id);
            $monitorTags = $allTags->get($m->id) ?? collect();

            return [
                'id' => $m->id,
                'name' => $m->url,
                'url' => $m->url,
                'host' => $host,
                'uptime_status' => $m->uptime_status,
                'uptime_check_enabled' => (bool) $m->uptime_check_enabled,
                'favicon' => "https://s2.googleusercontent.com/s2/favicons?domain={$host}&sz=32",
                'last_check_date' => $m->uptime_last_check_date,
                'last_check_date_human' => $m->uptime_last_check_date ? \Illuminate\Support\Carbon::parse($m->uptime_last_check_date)->diffForHumans() : null,
                'today_uptime_percentage' => $monitorUptime ? (float) $monitorUptime->uptime_percentage : 0,
                'tags' => $monitorTags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => null]),
                'statistics' => [
                    'uptime_24h' => $monitorStats->uptime_24h ?? null,
                    'avg_response_time_24h' => $monitorStats->avg_response_time_24h ?? null,
                    'incidents_24h' => $monitorStats->incidents_24h ?? 0,
                ],
            ];
        });

        return Inertia::render('monitors/Compact', [
            'monitors' => ['data' => $data],
            'availableTags' => $availableTags,
        ]);
    }
}
