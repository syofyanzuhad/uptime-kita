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
