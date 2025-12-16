<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Services\SmartRetryResult;
use App\Services\SmartRetryService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Helpers\Period;

class ConfirmMonitorDowntimeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $monitorId,
        public string $failureReason,
        public ?int $previousFailureCount = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SmartRetryService $smartRetry): void
    {
        $monitor = Monitor::withoutGlobalScopes()->find($this->monitorId);

        if (! $monitor) {
            Log::warning('ConfirmMonitorDowntimeJob: Monitor not found', [
                'monitor_id' => $this->monitorId,
            ]);

            return;
        }

        // If monitor is no longer enabled, skip
        if (! $monitor->uptime_check_enabled) {
            Log::info('ConfirmMonitorDowntimeJob: Monitor disabled, skipping confirmation', [
                'monitor_id' => $this->monitorId,
            ]);

            return;
        }

        // If monitor has recovered since the failure, skip
        if ($monitor->uptime_status === 'up') {
            Log::info('ConfirmMonitorDowntimeJob: Monitor already recovered, marking as transient failure', [
                'monitor_id' => $this->monitorId,
                'url' => (string) $monitor->url,
            ]);
            $this->logTransientFailure($monitor);

            return;
        }

        // Get per-monitor settings or use sensitivity preset
        $sensitivity = $monitor->sensitivity ?? 'medium';
        $preset = SmartRetryService::getPreset($sensitivity);

        $options = [
            'retries' => $monitor->confirmation_retries ?? $preset['retries'],
            'initial_delay_ms' => $preset['initial_delay_ms'],
            'backoff_multiplier' => $preset['backoff_multiplier'],
        ];

        // Perform smart confirmation check
        $result = $smartRetry->performSmartCheck($monitor, $options);

        if ($result->isSuccess()) {
            Log::info('ConfirmMonitorDowntimeJob: Confirmation check passed, marking as transient failure', [
                'monitor_id' => $this->monitorId,
                'url' => (string) $monitor->url,
                'attempts' => $result->getAttemptCount(),
            ]);

            $this->logTransientFailure($monitor);
            $this->resetMonitorStatus($monitor);
        } else {
            $this->confirmDowntime($monitor, $result);
        }
    }

    /**
     * Reset monitor status after transient failure.
     */
    protected function resetMonitorStatus(Monitor $monitor): void
    {
        $monitor->uptime_check_times_failed_in_a_row = 0;
        $monitor->uptime_status = 'up';
        $monitor->uptime_check_failure_reason = null;
        $monitor->saveQuietly();
    }

    /**
     * Confirm monitor downtime and fire event.
     */
    protected function confirmDowntime(Monitor $monitor, SmartRetryResult $result): void
    {
        Log::info('ConfirmMonitorDowntimeJob: Confirmed DOWN status', [
            'monitor_id' => $this->monitorId,
            'url' => (string) $monitor->url,
            'failure_reason' => $result->message ?? $this->failureReason,
            'attempts' => $result->getAttemptCount(),
        ]);

        // Update failure reason with smart retry result message
        if ($result->message) {
            $monitor->uptime_check_failure_reason = $result->message;
            $monitor->saveQuietly();
        }

        // Fire the UptimeCheckFailed event manually to trigger notifications
        $downtimePeriod = new Period(
            $monitor->uptime_status_last_change_date ?? Carbon::now(),
            Carbon::now()
        );
        event(new UptimeCheckFailed($monitor, $downtimePeriod));
    }

    /**
     * Log transient failure for tracking purposes.
     */
    protected function logTransientFailure(Monitor $monitor): void
    {
        Log::info('ConfirmMonitorDowntimeJob: Transient failure detected', [
            'monitor_id' => $this->monitorId,
            'url' => (string) $monitor->url,
            'original_failure_reason' => $this->failureReason,
        ]);

        // Increment transient failure counter if the field exists
        if (isset($monitor->transient_failures_count)) {
            $monitor->transient_failures_count = ($monitor->transient_failures_count ?? 0) + 1;
            $monitor->saveQuietly();
        }
    }
}
