<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        // Sorting parameters
        $sortBy = $request->input('sort', 'url');
        $direction = $request->input('direction', 'asc');
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        $today = now()->toDateString();

        // Cache key based on input parameters and user type
        $cacheKey = 'monitors_compact_'.md5(json_encode([$search, $isGuest, $sortBy, $direction]));

        $data = cache()->remember($cacheKey, 30, function () use ($search, $isGuest, $sortBy, $direction, $today) {
            // 1. Fetch RAW data
            $query = DB::table('monitors')
                ->select([
                    'monitors.id',
                    'monitors.url',
                    'monitors.uptime_status',
                    'monitors.uptime_check_enabled',
                    'monitors.uptime_last_check_date',
                    'monitors.uptime_check_interval_in_minutes',
                    'monitors.certificate_check_enabled',
                    'monitors.certificate_status',
                    'monitors.certificate_expiration_date',
                ])
                ->where('monitors.uptime_check_enabled', 1)
                ->when($isGuest, fn ($q) => $q->where('monitors.is_public', 1))
                ->when($search, function ($q) use ($search) {
                    $q->where(fn ($sq) => $sq->where('monitors.url', 'like', "%$search%")->orWhere('monitors.display_name', 'like', "%$search%"));
                });

            // 2. Handle Sorting (Refined to push NULLs to the bottom)
            if ($sortBy === 'today_uptime_percentage') {
                $query->leftJoin('monitor_uptime_dailies', function ($join) use ($today) {
                    $join->on('monitors.id', '=', 'monitor_uptime_dailies.monitor_id')
                        ->where('monitor_uptime_dailies.date', '=', $today);
                })
                    ->orderByRaw('monitor_uptime_dailies.uptime_percentage IS NULL ASC')
                    ->orderBy('monitor_uptime_dailies.uptime_percentage', $direction);
            } elseif ($sortBy === 'avg_response_time_24h') {
                $query->leftJoin('monitor_statistics', 'monitors.id', '=', 'monitor_statistics.monitor_id')
                    ->orderByRaw('monitor_statistics.avg_response_time_24h IS NULL ASC')
                    ->orderBy('monitor_statistics.avg_response_time_24h', $direction);
            } elseif ($sortBy === 'uptime_status') {
                $query->orderBy('monitors.uptime_status', $direction);
            } elseif ($sortBy === 'last_checked') {
                $query->orderByRaw('monitors.uptime_last_check_date IS NULL ASC')
                    ->orderBy('monitors.uptime_last_check_date', $direction);
            } else {
                $query->orderBy('monitors.url', $direction);
            }

            $monitors = $query->get();

            if ($monitors->isEmpty()) {
                return collect();
            }

            $ids = $monitors->pluck('id')->toArray();

            // 3. Fetch Related Data in bulk
            $uptimes = DB::table('monitor_uptime_dailies')
                ->whereIn('monitor_id', $ids)
                ->where('date', $today)
                ->get()
                ->keyBy('monitor_id');

            $stats = DB::table('monitor_statistics')
                ->whereIn('monitor_id', $ids)
                ->select(['monitor_id', 'uptime_24h', 'avg_response_time_24h', 'incidents_24h'])
                ->get()
                ->keyBy('monitor_id');

            $allTags = DB::table('tags')
                ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
                ->whereIn('taggables.taggable_id', $ids)
                ->where('taggables.taggable_type', Monitor::class)
                ->select(['tags.id', 'tags.name', 'taggables.taggable_id'])
                ->get()
                ->groupBy('taggable_id');

            $parseTagName = function ($jsonName) {
                try {
                    $data = json_decode($jsonName, true);

                    return $data['en'] ?? array_values($data)[0] ?? $jsonName;
                } catch (\Exception $e) {
                    return $jsonName;
                }
            };

            // 4. Assemble Payload
            return $monitors->map(function ($m) use ($uptimes, $stats, $allTags, $parseTagName) {
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
                    'last_check_date_human' => $m->uptime_last_check_date ? Carbon::parse($m->uptime_last_check_date)->diffForHumans() : null,
                    'uptime_check_interval' => (int) $m->uptime_check_interval_in_minutes,
                    'certificate_check_enabled' => (bool) $m->certificate_check_enabled,
                    'certificate_status' => $m->certificate_status,
                    'certificate_expiration_date' => $m->certificate_expiration_date,
                    'today_uptime_percentage' => $monitorUptime ? (float) $monitorUptime->uptime_percentage : 0,
                    'tags' => $monitorTags->map(fn ($t) => ['id' => $t->id, 'name' => $parseTagName($t->name), 'color' => null]),
                    'statistics' => [
                        'uptime_24h' => $monitorStats->uptime_24h ?? null,
                        'avg_response_time_24h' => $monitorStats->avg_response_time_24h ?? null,
                        'incidents_24h' => $monitorStats->incidents_24h ?? 0,
                    ],
                ];
            });
        });

        // Cache available tags separately for better granularity
        $availableTagsCacheKey = 'monitors_compact_available_tags_'.($isGuest ? 'guest' : 'auth');
        $availableTags = cache()->remember($availableTagsCacheKey, 300, function () use ($isGuest) {
            $tagQuery = DB::table('tags')
                ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
                ->where('taggables.taggable_type', Monitor::class)
                ->select(['tags.id', 'tags.name']);

            if ($isGuest) {
                $tagQuery->join('monitors', 'taggables.taggable_id', '=', 'monitors.id')
                    ->where('monitors.is_public', 1);
            }

            $tags = $tagQuery->distinct()->get();

            $parseTagName = function ($jsonName) {
                try {
                    $data = json_decode($jsonName, true);

                    return $data['en'] ?? array_values($data)[0] ?? $jsonName;
                } catch (\Exception $e) {
                    return $jsonName;
                }
            };

            return $tags->map(function ($t) use ($parseTagName) {
                return ['id' => $t->id, 'name' => $parseTagName($t->name), 'color' => null];
            });
        });

        return Inertia::render('monitors/Compact', [
            'monitors' => ['data' => $data],
            'availableTags' => $availableTags,
            'currentSort' => $sortBy,
            'currentDirection' => $direction,
        ]);
    }
}
