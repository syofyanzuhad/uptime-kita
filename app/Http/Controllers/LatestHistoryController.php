<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use App\Http\Resources\MonitorHistoryResource;

class LatestHistoryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Monitor $monitor)
    {
        $latestHistory = cache()->remember("monitor_{$monitor->id}_latest_history", 60, function () use ($monitor) {
            $latest = $monitor->latestHistory();
            if ($latest) {
                // Ensure monitor_id is set
                $latest->monitor_id = $monitor->id;
            }
            return $latest;
        });
        return response()->json([
            'latest_history' => $latestHistory ? new MonitorHistoryResource($latestHistory) : null,
        ]);
    }
}
