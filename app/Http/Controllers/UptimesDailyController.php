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
                    ]]
                ]);
            } else {
                return response()->json(['uptimes_daily' => []]);
            }
        }

        $uptimes = cache()->remember("monitor_{$monitor->id}_uptimes_daily", 60, function () use ($monitor) {
            return $monitor->uptimesDaily()->get()->map(function ($uptime) {
                return [
                    'date' => $uptime->date->toDateString(),
                    'uptime_percentage' => $uptime->uptime_percentage,
                ];
            });
        });
        return response()->json(['uptimes_daily' => $uptimes]);
    }
}
