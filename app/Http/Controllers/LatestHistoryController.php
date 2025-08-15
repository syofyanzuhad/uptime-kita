<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorHistoryResource;
use App\Models\Monitor;
use Illuminate\Http\Request;

class LatestHistoryController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Monitor $monitor)
    {
        $latestHistory = cache()->remember("monitor_{$monitor->id}_latest_history", 60, function () use ($monitor) {
            return $monitor->latestHistory()->first();
        });

        return response()->json([
            'latest_history' => $latestHistory ? new MonitorHistoryResource($latestHistory) : null,
        ]);
    }
}
