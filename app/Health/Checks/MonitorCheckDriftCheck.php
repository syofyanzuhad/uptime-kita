<?php

namespace App\Health\Checks;

use App\Models\Monitor;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class MonitorCheckDriftCheck extends Check
{
    protected int $warnDriftMinutes = 2;

    protected int $failDriftMinutes = 5;

    public function warnWhenDriftExceedsMinutes(int $minutes): self
    {
        $this->warnDriftMinutes = $minutes;

        return $this;
    }

    public function failWhenDriftExceedsMinutes(int $minutes): self
    {
        $this->failDriftMinutes = $minutes;

        return $this;
    }

    public function run(): Result
    {
        $result = Result::make()->ok()->shortSummary('All monitors on schedule');

        // Check enabled monitors that have been checked at least once
        $monitors = Monitor::withoutGlobalScopes()
            ->where('uptime_check_enabled', true)
            ->whereNotNull('uptime_last_check_date')
            ->get();

        if ($monitors->isEmpty()) {
            return $result->shortSummary('No active monitors');
        }

        $now = now();
        $driftingMonitors = [];
        $maxDriftObserved = 0;

        foreach ($monitors as $monitor) {
            $interval = (int) $monitor->uptime_check_interval_in_minutes;
            $lastChecked = $monitor->uptime_last_check_date;

            if (! $lastChecked) {
                continue;
            }

            // Expected next check was at $lastChecked + $interval minutes
            $minutesSinceLastCheck = (int) $lastChecked->diffInMinutes($now, false);
            $driftMinutes = $minutesSinceLastCheck - $interval;

            if ($driftMinutes > 0) {
                if ($driftMinutes > $maxDriftObserved) {
                    $maxDriftObserved = $driftMinutes;
                }

                if ($driftMinutes >= $this->warnDriftMinutes) {
                    $driftingMonitors[] = [
                        'id' => $monitor->id,
                        'url' => (string) $monitor->url,
                        'interval' => $interval,
                        'drift_minutes' => $driftMinutes,
                    ];
                }
            }
        }

        if (empty($driftingMonitors)) {
            return $result;
        }

        $count = count($driftingMonitors);
        $result->meta([
            'drifting_monitors_count' => $count,
            'max_drift_minutes' => $maxDriftObserved,
            'drifting_monitors' => array_slice($driftingMonitors, 0, 5),
        ]);

        if ($maxDriftObserved >= $this->failDriftMinutes) {
            return $result->failed("{$count} monitor(s) delayed by up to {$maxDriftObserved} min");
        }

        return $result->warning("{$count} monitor(s) delayed by up to {$maxDriftObserved} min");
    }
}
