<?php

namespace App\Jobs;

use App\Models\Monitor;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
    public function handle(): void
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

        // Perform confirmation check
        $isStillDown = $this->performConfirmationCheck($monitor);

        if ($isStillDown) {
            Log::info('ConfirmMonitorDowntimeJob: Confirmed DOWN status', [
                'monitor_id' => $this->monitorId,
                'url' => (string) $monitor->url,
                'failure_reason' => $this->failureReason,
            ]);

            // Fire the UptimeCheckFailed event manually to trigger notifications
            // The Spatie package will handle the failure counter
            $downtimePeriod = new Period(
                $monitor->uptime_status_last_change_date ?? Carbon::now(),
                Carbon::now()
            );
            event(new UptimeCheckFailed($monitor, $downtimePeriod));
        } else {
            Log::info('ConfirmMonitorDowntimeJob: Confirmation check passed, marking as transient failure', [
                'monitor_id' => $this->monitorId,
                'url' => (string) $monitor->url,
            ]);

            $this->logTransientFailure($monitor);

            // Reset the failure counter since it was a transient issue
            $monitor->uptime_check_times_failed_in_a_row = 0;
            $monitor->uptime_status = 'up';
            $monitor->uptime_check_failure_reason = null;
            $monitor->saveQuietly();
        }
    }

    /**
     * Perform a confirmation HTTP check.
     */
    protected function performConfirmationCheck(Monitor $monitor): bool
    {
        $timeout = config('uptime-monitor.confirmation_check.timeout_seconds', 5);

        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
            'http_errors' => false,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => false,
                'protocols' => ['http', 'https'],
            ],
        ]);

        try {
            $method = $monitor->uptime_check_method ?? 'GET';
            $url = (string) $monitor->url;

            $options = [];

            // Add custom headers if configured
            if ($monitor->uptime_check_additional_headers) {
                $headers = is_array($monitor->uptime_check_additional_headers)
                    ? $monitor->uptime_check_additional_headers
                    : json_decode($monitor->uptime_check_additional_headers, true);

                if ($headers) {
                    $options['headers'] = $headers;
                }
            }

            // Add payload if configured
            if ($monitor->uptime_check_payload) {
                $options['body'] = $monitor->uptime_check_payload;
            }

            $response = $client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();

            // Check if status code is acceptable
            $expectedStatusCode = $monitor->expected_status_code ?? 200;
            $additionalStatusCodes = config('uptime-monitor.uptime_check.additional_status_codes', []);

            $acceptableStatusCodes = array_merge([$expectedStatusCode], $additionalStatusCodes, [200, 201, 204, 301, 302]);

            if (! in_array($statusCode, $acceptableStatusCodes)) {
                Log::debug('ConfirmMonitorDowntimeJob: Unacceptable status code', [
                    'monitor_id' => $this->monitorId,
                    'status_code' => $statusCode,
                    'expected' => $acceptableStatusCodes,
                ]);

                return true; // Still down
            }

            // Check for look_for_string if configured
            if ($monitor->look_for_string) {
                $body = (string) $response->getBody();
                if (stripos($body, $monitor->look_for_string) === false) {
                    Log::debug('ConfirmMonitorDowntimeJob: String not found in response', [
                        'monitor_id' => $this->monitorId,
                        'look_for_string' => $monitor->look_for_string,
                    ]);

                    return true; // Still down
                }
            }

            return false; // Recovered
        } catch (RequestException $e) {
            Log::debug('ConfirmMonitorDowntimeJob: Request exception', [
                'monitor_id' => $this->monitorId,
                'error' => $e->getMessage(),
            ]);

            return true; // Still down
        } catch (\Exception $e) {
            Log::error('ConfirmMonitorDowntimeJob: Unexpected error', [
                'monitor_id' => $this->monitorId,
                'error' => $e->getMessage(),
            ]);

            return true; // Assume still down on unexpected errors
        }
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
