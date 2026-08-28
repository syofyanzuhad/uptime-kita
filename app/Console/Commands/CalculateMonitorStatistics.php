<?php

namespace App\Console\Commands;

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorStatistic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateMonitorStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:calculate-statistics {monitor?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and cache monitor statistics for efficient public page loading';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitorId = $this->argument('monitor');

        $query = Monitor::query()->where('is_public', true);

        if ($monitorId) {
            $query->where('id', $monitorId);
        } else {
            $query->where('uptime_check_enabled', true);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No public monitors found.');

            return;
        }

        $this->info("Calculating statistics for {$count} monitor(s)...");
        $progressBar = $this->output->createProgressBar($count);

        $query->lazy()->each(function (Monitor $monitor) use ($progressBar) {
            $this->calculateStatistics($monitor);
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->newLine();
        $this->info('Monitor statistics calculated successfully!');
    }

    private function calculateStatistics(Monitor $monitor): void
    {
        $now = now();
        $periods = [
            '1h' => $now->copy()->subHour(),
            '24h' => $now->copy()->subDay(),
            '7d' => $now->copy()->subDays(7),
            '30d' => $now->copy()->subDays(30),
            '90d' => $now->copy()->subDays(90),
        ];

        // 1. Calculate 1h and 24h stats using the raw history table
        $stats24h = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $periods['24h'])
            ->selectRaw("
                COUNT(*) as total_24h,
                SUM(CASE WHEN uptime_status = 'up' THEN 1 ELSE 0 END) as up_24h,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as total_1h,
                SUM(CASE WHEN created_at >= ? AND uptime_status = 'up' THEN 1 ELSE 0 END) as up_1h,
                SUM(CASE WHEN uptime_status != 'up' THEN 1 ELSE 0 END) as incidents_24h,
                AVG(CASE WHEN response_time IS NOT NULL THEN response_time END) as avg_resp_24h,
                MIN(CASE WHEN response_time IS NOT NULL THEN response_time END) as min_resp_24h,
                MAX(CASE WHEN response_time IS NOT NULL THEN response_time END) as max_resp_24h
            ", [$periods['1h'], $periods['1h']])
            ->first();

        // 2. Calculate 7d, 30d, 90d stats using the daily rollups table
        $dailyStats = DB::table('monitor_uptime_dailies')
            ->where('monitor_id', $monitor->id)
            ->where('date', '>=', $periods['90d']->toDateString())
            ->selectRaw('
                SUM(CASE WHEN date >= ? THEN total_checks ELSE 0 END) as total_7d,
                SUM(CASE WHEN date >= ? THEN (total_checks - failed_checks) ELSE 0 END) as up_7d,
                SUM(CASE WHEN date >= ? THEN failed_checks ELSE 0 END) as incidents_7d,
                SUM(CASE WHEN date >= ? THEN total_checks ELSE 0 END) as total_30d,
                SUM(CASE WHEN date >= ? THEN (total_checks - failed_checks) ELSE 0 END) as up_30d,
                SUM(CASE WHEN date >= ? THEN failed_checks ELSE 0 END) as incidents_30d,
                SUM(total_checks) as total_90d,
                SUM(total_checks - failed_checks) as up_90d
            ', [
                $periods['7d']->toDateString(), $periods['7d']->toDateString(), $periods['7d']->toDateString(),
                $periods['30d']->toDateString(), $periods['30d']->toDateString(), $periods['30d']->toDateString(),
            ])
            ->first();

        $calculateUptime = function ($up, $total) {
            return ($total > 0) ? round(((float) $up / (float) $total) * 100, 2) : 100.0;
        };

        // Get recent history for last 100 minutes
        $recentHistory = $this->getRecentHistory($monitor);

        // Upsert statistics record
        MonitorStatistic::updateOrCreate(
            ['monitor_id' => $monitor->id],
            [
                'uptime_1h' => $calculateUptime($stats24h->up_1h ?? 0, $stats24h->total_1h ?? 0),
                'uptime_24h' => $calculateUptime($stats24h->up_24h ?? 0, $stats24h->total_24h ?? 0),
                'uptime_7d' => $calculateUptime($dailyStats->up_7d ?? 0, $dailyStats->total_7d ?? 0),
                'uptime_30d' => $calculateUptime($dailyStats->up_30d ?? 0, $dailyStats->total_30d ?? 0),
                'uptime_90d' => $calculateUptime($dailyStats->up_90d ?? 0, $dailyStats->total_90d ?? 0),
                'avg_response_time_24h' => ($stats24h && $stats24h->avg_resp_24h) ? (int) round($stats24h->avg_resp_24h) : null,
                'min_response_time_24h' => ($stats24h && $stats24h->min_resp_24h) ? (int) $stats24h->min_resp_24h : null,
                'max_response_time_24h' => ($stats24h && $stats24h->max_resp_24h) ? (int) $stats24h->max_resp_24h : null,
                'incidents_24h' => (int) ($stats24h->incidents_24h ?? 0),
                'incidents_7d' => (int) ($dailyStats->incidents_7d ?? 0),
                'incidents_30d' => (int) ($dailyStats->incidents_30d ?? 0),
                'total_checks_24h' => (int) ($stats24h->total_24h ?? 0),
                'total_checks_7d' => (int) ($dailyStats->total_7d ?? 0),
                'total_checks_30d' => (int) ($dailyStats->total_30d ?? 0),
                'recent_history_100m' => $recentHistory,
                'calculated_at' => $now,
            ]
        );
    }

    private function getRecentHistory(Monitor $monitor): array
    {
        $oneHundredMinutesAgo = now()->subMinutes(100);

        // Optimized retrieval - covering index will be used
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $oneHundredMinutesAgo)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->select(['created_at', 'uptime_status', 'response_time', 'message'])
            ->get();

        // Transform to a lighter format for JSON storage
        return $histories->map(function ($history) {
            return [
                'created_at' => $history->created_at->toISOString(),
                'uptime_status' => $history->uptime_status,
                'response_time' => $history->response_time,
                'message' => $history->message,
            ];
        })->toArray();
    }
}
