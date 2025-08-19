<?php

namespace App\Console\Commands;

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorStatistic;
use Carbon\Carbon;
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
        
        if ($monitorId) {
            $monitors = Monitor::where('id', $monitorId)->where('is_public', true)->get();
        } else {
            $monitors = Monitor::where('is_public', true)->where('uptime_check_enabled', true)->get();
        }

        if ($monitors->isEmpty()) {
            $this->warn('No public monitors found.');
            return;
        }

        $this->info("Calculating statistics for {$monitors->count()} monitor(s)...");

        $progressBar = $this->output->createProgressBar($monitors->count());

        foreach ($monitors as $monitor) {
            $this->calculateStatistics($monitor);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Monitor statistics calculated successfully!');
    }

    private function calculateStatistics(Monitor $monitor): void
    {
        $now = now();

        // Calculate uptime percentages for different periods
        $uptimeStats = [
            '1h' => $this->calculateUptimePercentage($monitor, $now->copy()->subHour()),
            '24h' => $this->calculateUptimePercentage($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(30)),
            '90d' => $this->calculateUptimePercentage($monitor, $now->copy()->subDays(90)),
        ];

        // Calculate response time stats for last 24h
        $responseTimeStats = $this->calculateResponseTimeStats($monitor, $now->copy()->subDay());

        // Calculate incident counts
        $incidentCounts = [
            '24h' => $this->calculateIncidentCount($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateIncidentCount($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateIncidentCount($monitor, $now->copy()->subDays(30)),
        ];

        // Calculate total checks
        $totalChecks = [
            '24h' => $this->calculateTotalChecks($monitor, $now->copy()->subDay()),
            '7d' => $this->calculateTotalChecks($monitor, $now->copy()->subDays(7)),
            '30d' => $this->calculateTotalChecks($monitor, $now->copy()->subDays(30)),
        ];

        // Get recent history for last 100 minutes
        $recentHistory = $this->getRecentHistory($monitor);

        // Upsert statistics record
        MonitorStatistic::updateOrCreate(
            ['monitor_id' => $monitor->id],
            [
                'uptime_1h' => $uptimeStats['1h'],
                'uptime_24h' => $uptimeStats['24h'],
                'uptime_7d' => $uptimeStats['7d'],
                'uptime_30d' => $uptimeStats['30d'],
                'uptime_90d' => $uptimeStats['90d'],
                'avg_response_time_24h' => $responseTimeStats['avg'],
                'min_response_time_24h' => $responseTimeStats['min'],
                'max_response_time_24h' => $responseTimeStats['max'],
                'incidents_24h' => $incidentCounts['24h'],
                'incidents_7d' => $incidentCounts['7d'],
                'incidents_30d' => $incidentCounts['30d'],
                'total_checks_24h' => $totalChecks['24h'],
                'total_checks_7d' => $totalChecks['7d'],
                'total_checks_30d' => $totalChecks['30d'],
                'recent_history_100m' => $recentHistory,
                'calculated_at' => $now,
            ]
        );
    }

    private function calculateUptimePercentage(Monitor $monitor, Carbon $startDate): float
    {
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        if ($histories->isEmpty()) {
            return 100.0;
        }

        $upCount = $histories->where('uptime_status', 'up')->count();
        $totalCount = $histories->count();

        return round(($upCount / $totalCount) * 100, 2);
    }

    private function calculateResponseTimeStats(Monitor $monitor, Carbon $startDate): array
    {
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('response_time')
            ->get();

        if ($histories->isEmpty()) {
            return ['avg' => null, 'min' => null, 'max' => null];
        }

        $responseTimes = $histories->pluck('response_time');

        return [
            'avg' => (int) round($responseTimes->avg()),
            'min' => $responseTimes->min(),
            'max' => $responseTimes->max(),
        ];
    }

    private function calculateIncidentCount(Monitor $monitor, Carbon $startDate): int
    {
        return MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->where('uptime_status', '!=', 'up')
            ->count();
    }

    private function calculateTotalChecks(Monitor $monitor, Carbon $startDate): int
    {
        return MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $startDate)
            ->count();
    }

    private function getRecentHistory(Monitor $monitor): array
    {
        $oneHundredMinutesAgo = now()->subMinutes(100);
        
        $histories = MonitorHistory::where('monitor_id', $monitor->id)
            ->where('created_at', '>=', $oneHundredMinutesAgo)
            ->orderBy('created_at', 'desc')
            ->limit(100)
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
