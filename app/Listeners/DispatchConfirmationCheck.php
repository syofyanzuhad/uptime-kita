<?php

namespace App\Listeners;

use App\Jobs\ConfirmMonitorDowntimeJob;
use App\Services\SmartRetryService;
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
            return true; // Let other listeners handle the event
        }

        $failureCount = $monitor->uptime_check_times_failed_in_a_row;

        // Only dispatch confirmation check on first failure of a new incident
        // If already at threshold, the event was already confirmed
        if ($failureCount === 1) {
            // Get per-monitor delay or use sensitivity preset
            $delay = $this->getConfirmationDelay($monitor);

            ConfirmMonitorDowntimeJob::dispatch(
                $monitor->id,
                $monitor->uptime_check_failure_reason ?? 'Unknown failure',
                $failureCount
            )->delay(now()->addSeconds($delay));

            // Stop event propagation for first failure
            // We'll fire a new event from the job if confirmed
            return false;
        }

        // Let other listeners handle the event for subsequent failures
        return true;
    }

    /**
     * Get confirmation delay for a monitor.
     *
     * Priority:
     * 1. Per-monitor custom delay (confirmation_delay_seconds)
     * 2. Sensitivity preset delay
     * 3. Global config default
     */
    protected function getConfirmationDelay($monitor): int
    {
        // Check for custom per-monitor delay
        if ($monitor->confirmation_delay_seconds !== null) {
            return $monitor->confirmation_delay_seconds;
        }

        // Use sensitivity preset
        $sensitivity = $monitor->sensitivity ?? 'medium';
        $preset = SmartRetryService::getPreset($sensitivity);

        return $preset['confirmation_delay'] ?? config('uptime-monitor.confirmation_check.delay_seconds', 30);
    }
}
