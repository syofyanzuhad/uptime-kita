<?php

namespace App\Console\Commands;

use App\Jobs\CalculateSingleMonitorUptimeJob;
use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class BackfillDailyUptimesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uptime:backfill-dailies {--days=30 : How many days to look back} {--monitor-id= : Backfill for a specific monitor} {--force : Recalculate even if record exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find missing daily uptime records and recalculate them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $monitorId = $this->option('monitor-id');
        $force = $this->option('force');

        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->subDay()->endOfDay(); // Only up to yesterday

        $this->info("Scanning for missing records from {$startDate->toDateString()} to {$endDate->toDateString()}");

        $monitors = $monitorId ? Monitor::where('id', $monitorId)->get() : Monitor::all();

        if ($monitors->isEmpty()) {
            $this->error('No monitors found.');

            return 1;
        }

        $totalJobs = 0;
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($monitors as $monitor) {
            $this->comment("Processing monitor: {$monitor->url} (ID: {$monitor->id})");

            $existingDates = MonitorUptimeDaily::where('monitor_id', $monitor->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->toArray();

            foreach ($period as $date) {
                $dateString = $date->toDateString();

                if ($force || ! in_array($dateString, $existingDates)) {
                    $this->line("  - Dispatching calculation for {$dateString}");
                    dispatch(new CalculateSingleMonitorUptimeJob($monitor->id, $dateString));
                    $totalJobs++;
                }
            }
        }

        $this->info("Successfully dispatched {$totalJobs} backfill jobs.");

        return 0;
    }
}
