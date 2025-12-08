<?php

namespace App\Listeners;

use App\Jobs\ConfirmMonitorDowntimeJob;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;

class DispatchConfirmationCheck
{
    /**
     * Handle the event.
     *
     * This listener intercepts UptimeCheckFailed events and dispatches
     * a confirmation check job with a delay to reduce false positives.
     *
     * The confirmation check will verify if the monitor is truly down
     * before allowing notifications to be sent.
     */
    public function handle(UptimeCheckFailed $event): bool
    {
        $monitor = $event->monitor;

        // Check if confirmation check is enabled
        if (! config('uptime-monitor.confirmation_check.enabled', true)) {
            Log::debug('DispatchConfirmationCheck: Confirmation check disabled, proceeding with original event', [
                'monitor_id' => $monitor->id,
            ]);

            return true; // Let other listeners handle the event
        }

        $failureCount = $monitor->uptime_check_times_failed_in_a_row;
        $threshold = config('uptime-monitor.uptime_check.fire_monitor_failed_event_after_consecutive_failures', 3);

        // Only dispatch confirmation check on first failure of a new incident
        // If already at threshold, the event was already confirmed
        if ($failureCount === 1) {
            $delay = config('uptime-monitor.confirmation_check.delay_seconds', 30);

            Log::info('DispatchConfirmationCheck: Dispatching confirmation check', [
                'monitor_id' => $monitor->id,
                'url' => (string) $monitor->url,
                'failure_count' => $failureCount,
                'delay_seconds' => $delay,
            ]);

            ConfirmMonitorDowntimeJob::dispatch(
                $monitor->id,
                $monitor->uptime_check_failure_reason ?? 'Unknown failure',
                $failureCount
            )->delay(now()->addSeconds($delay));

            // Stop event propagation for first failure
            // We'll fire a new event from the job if confirmed
            return false;
        }

        Log::debug('DispatchConfirmationCheck: Failure count > 1, letting event propagate', [
            'monitor_id' => $monitor->id,
            'failure_count' => $failureCount,
            'threshold' => $threshold,
        ]);

        // Let other listeners handle the event for subsequent failures
        return true;
    }
}
