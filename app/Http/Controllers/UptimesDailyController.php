<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class UptimesDailyController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Monitor $monitor)
    {
        $date = $request->query('date');
        if ($date) {
            $uptime = $monitor->uptimesDaily()->whereDate('date', $date)->first();
            if ($uptime) {
                return response()->json([
                    'uptimes_daily' => [[
                        'date' => $uptime->date->toDateString(),
                        'uptime_percentage' => $uptime->uptime_percentage,
                    ]],
                ]);
            } else {
                return response()->json(['uptimes_daily' => []]);
            }
        }

        $limit = $request->query('limit');
        $limit = (is_numeric($limit) && (int) $limit > 0) ? (int) $limit : 90;

        // Don't allow more than 90 days for consistency
        if ($limit > 90) {
            $limit = 90;
        }

        $cacheKey = ($limit === 90)
            ? "monitor_{$monitor->id}_uptimes_daily"
            : "monitor_{$monitor->id}_uptimes_daily_limit_{$limit}";

        $uptimes = cache()->remember($cacheKey, 60, function () use ($monitor, $limit) {
            return $monitor->uptimesDaily()
                ->reorder('date', 'desc')
                ->take($limit)
                ->get()
                ->reverse()
                ->values()
                ->map(function ($uptime) {
                    return [
                        'date' => $uptime->date->toDateString(),
                        'uptime_percentage' => $uptime->uptime_percentage,
                    ];
                });
        });

        return response()->json(['uptimes_daily' => $uptimes]);
    }
}
