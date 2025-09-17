<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebugStatsController extends Controller
{
    public function __invoke(Request $request)
    {
        $results = [
            'timezone' => config('app.timezone'),
            'current_time' => now()->toString(),
            'today' => today()->toString(),
            'cache_keys' => [
                'daily_checks' => cache()->has('public_monitors_daily_checks'),
                'monthly_checks' => cache()->has('public_monitors_monthly_checks'),
            ],
        ];

        // Get public monitor count
        $results['public_monitors_count'] = Monitor::where('is_public', true)->count();

        // Get monitor_statistics data
        $results['monitor_statistics'] = [
            'total_rows' => DB::table('monitor_statistics')->count(),
            'sum_24h' => DB::table('monitor_statistics')
                ->join('monitors', 'monitor_statistics.monitor_id', '=', 'monitors.id')
                ->where('monitors.is_public', true)
                ->sum('monitor_statistics.total_checks_24h'),
            'sum_30d' => DB::table('monitor_statistics')
                ->join('monitors', 'monitor_statistics.monitor_id', '=', 'monitors.id')
                ->where('monitors.is_public', true)
                ->sum('monitor_statistics.total_checks_30d'),
        ];

        // Get monitor_histories data
        $results['monitor_histories'] = [
            'total_rows' => MonitorHistory::count(),
            'public_monitor_checks_today' => MonitorHistory::whereIn('monitor_id', function ($query) {
                    $query->select('id')
                        ->from('monitors')
                        ->where('is_public', true);
                })
                ->whereDate('checked_at', today())
                ->count(),
            'public_monitor_checks_this_month' => MonitorHistory::whereIn('monitor_id', function ($query) {
                    $query->select('id')
                        ->from('monitors')
                        ->where('is_public', true);
                })
                ->whereMonth('checked_at', now()->month)
                ->whereYear('checked_at', now()->year)
                ->count(),
            'latest_check' => MonitorHistory::latest('checked_at')->first(['checked_at'])?->checked_at,
        ];

        // Raw SQL queries for verification
        $results['raw_queries'] = [
            'daily_direct' => DB::select("
                SELECT COUNT(*) as count
                FROM monitor_histories
                WHERE monitor_id IN (
                    SELECT id FROM monitors WHERE is_public = 1
                )
                AND DATE(checked_at) = DATE('now', 'localtime')
            ")[0]->count ?? 0,
            'monthly_direct' => DB::select("
                SELECT COUNT(*) as count
                FROM monitor_histories
                WHERE monitor_id IN (
                    SELECT id FROM monitors WHERE is_public = 1
                )
                AND strftime('%Y-%m', checked_at) = strftime('%Y-%m', 'now', 'localtime')
            ")[0]->count ?? 0,
        ];

        // Clear cache if requested
        if ($request->get('clear_cache')) {
            cache()->forget('public_monitors_daily_checks');
            cache()->forget('public_monitors_monthly_checks');
            $results['cache_cleared'] = true;
        }

        return response()->json($results);
    }
}