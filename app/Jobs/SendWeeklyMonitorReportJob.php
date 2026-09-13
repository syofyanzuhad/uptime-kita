<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorIncident;
use App\Models\MonitorUptimeDaily;
use App\Models\User;
use App\Notifications\WeeklyMonitorReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendWeeklyMonitorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Skip if user has opted out of weekly reports
        if (! $this->user->weekly_report_enabled) {
            return;
        }

        // 2. Load user's active monitors with their statistics
        $monitors = $this->user->monitors()
            ->where('uptime_check_enabled', true)
            ->with(['statistic', 'statistics'])
            ->get();

        if ($monitors->isEmpty()) {
            return;
        }

        // 3. Define the weekly reporting period (previous Monday -> Sunday)
        $userTz = $this->user->weekly_report_timezone ?: 'UTC';
        $now = Carbon::now($userTz);
        $periodStart = $now->copy()->subWeek()->startOfWeek();
        $periodEnd = $now->copy()->subWeek()->endOfWeek();
        $periodString = $periodStart->format('M d').' – '.$periodEnd->format('M d, Y');

        $startDateStr = $periodStart->toDateString();
        $endDateStr = $periodEnd->toDateString();

        // 4. Build per-monitor summaries
        $summaries = $monitors->map(function (Monitor $monitor) use ($startDateStr, $endDateStr) {
            $stat = $monitor->statistic ?? $monitor->statistics;

            // Current 7-day uptime
            $uptime7d = $stat?->uptime_7d !== null ? (float) $stat->uptime_7d : 100.0;

            // Previous week uptime (for trend indicator)
            $prevWeekStart = Carbon::parse($startDateStr)->subWeek()->toDateString();
            $prevWeekEnd = Carbon::parse($endDateStr)->subWeek()->toDateString();

            $prevDailyAvg = MonitorUptimeDaily::where('monitor_id', $monitor->id)
                ->whereBetween('date', [$prevWeekStart, $prevWeekEnd])
                ->avg('uptime_percentage');

            $uptime7dPrev = $prevDailyAvg !== null ? round((float) $prevDailyAvg, 2) : $uptime7d;

            // Total downtime minutes in the last 7 days
            $downtimeMins = (int) MonitorIncident::where('monitor_id', $monitor->id)
                ->where('started_at', '>=', now()->subDays(7))
                ->sum('duration_minutes');

            $avgResponseMs = (int) ($stat?->avg_response_time_24h ?? 0);
            $incidents7d = (int) ($stat?->incidents_7d ?? MonitorIncident::where('monitor_id', $monitor->id)->where('started_at', '>=', now()->subDays(7))->count());
            $isUpNow = $monitor->uptime_status === 'up';

            return [
                'monitor_id' => (int) $monitor->id,
                'display_name' => (string) ($monitor->display_name ?: $monitor->host ?: $monitor->raw_url ?: "Monitor #{$monitor->id}"),
                'url' => (string) ($monitor->raw_url ?: $monitor->url),
                'uptime_7d' => round($uptime7d, 2),
                'uptime_7d_prev' => round($uptime7dPrev, 2),
                'avg_response_ms' => $avgResponseMs,
                'incidents_7d' => $incidents7d,
                'total_downtime_mins' => $downtimeMins,
                'is_up_now' => $isUpNow,
                'monitor_url' => url('/monitors/'.$monitor->id),
            ];
        });

        // 5. Calculate fleet totals
        $totalMonitors = $summaries->count();
        $upMonitors = $summaries->where('is_up_now', true)->count();
        $downMonitors = $totalMonitors - $upMonitors;
        $avgUptime = $totalMonitors > 0 ? round((float) $summaries->avg('uptime_7d'), 2) : 100.0;
        $totalDowntimeMins = (int) $summaries->sum('total_downtime_mins');

        $worstPerformer = $summaries->sortBy('uptime_7d')->first();
        $bestPerformer = $summaries->sortByDesc('uptime_7d')->sortBy('avg_response_ms')->first();

        $fleetTotals = [
            'total_monitors' => $totalMonitors,
            'up_monitors' => $upMonitors,
            'down_monitors' => $downMonitors,
            'average_uptime' => $avgUptime,
            'total_downtime_mins' => $totalDowntimeMins,
            'worst_performer' => $worstPerformer,
            'best_performer' => $bestPerformer,
        ];

        // 6. Dispatch notification
        $this->user->notify(new WeeklyMonitorReport($monitors, $summaries, $fleetTotals, $periodString));
    }
}
