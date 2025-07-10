<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatisticMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = auth()->user();

        $statistics = Cache::remember('statistic-monitor:'.$user?->id, 60, function () use ($user) {
            $publicMonitorCount = Monitor::withoutGlobalScope('user')
                ->where('is_public', true)
                ->count();
            $privateMonitorCount = Monitor::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user?->id);
            })->where('is_public', false)->count();
            $totalMonitors = Monitor::withoutGlobalScope('user')->count();

            $nowInMinutes = now()->timestamp / 60;
            // get online monitors
            $onlineMonitors = Monitor::withoutGlobalScope('user')
                ->where('uptime_status', 'up')
                ->where('uptime_last_check_date', '>=', $nowInMinutes - 60)
                ->count();
            // get offline monitors
            $offlineMonitors = Monitor::withoutGlobalScope('user')
                ->where('uptime_status', 'down')
                ->where('uptime_last_check_date', '>=', $nowInMinutes - 60)
                ->count();

            // get unsubscribed monitors
            $unsubscribedMonitors = Monitor::withoutGlobalScope('user')
                ->where('is_public', true)
                ->whereDoesntHave('users', function ($query) use ($user) {
                    $query->where('user_id', $user?->id);
                })
                ->count();

            // get globally enabled monitors (uptime_check_enabled = true)
            $globallyEnabledMonitors = Monitor::withoutGlobalScope('user')
                ->withoutGlobalScope('enabled')
                ->where('uptime_check_enabled', true)
                ->count();

            // get globally disabled monitors (uptime_check_enabled = false)
            $globallyDisabledMonitors = Monitor::withoutGlobalScope('user')
                ->withoutGlobalScope('enabled')
                ->where('uptime_check_enabled', false)
                ->count();

            $data = [
                'public_monitor_count' => $publicMonitorCount,
                'private_monitor_count' => $privateMonitorCount,
                'total_monitors' => $totalMonitors,
                'online_monitors' => $onlineMonitors,
                'offline_monitors' => $offlineMonitors,
                'unsubscribed_monitors' => $unsubscribedMonitors,
                'globally_enabled_monitors' => $globallyEnabledMonitors,
                'globally_disabled_monitors' => $globallyDisabledMonitors,
            ];
            if ($user && $user->id === 1) {
                $data['user_count'] = \App\Models\User::count();
            }

            return $data;
        });

        return response()->json($statistics);
    }
}
