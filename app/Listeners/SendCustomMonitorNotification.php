<?php

namespace App\Listeners;

use App\Notifications\MonitorStatusChanged;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;

class SendCustomMonitorNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $monitor = $event->monitor;

        Log::info('SendCustomMonitorNotification: Event received', [
            'event_type' => get_class($event),
            'monitor_id' => $monitor->id,
            'monitor_url' => $monitor->url,
        ]);

        // Log history record immediately with accurate timing
        $this->logHistoryRecord($event, $monitor);

        // Get all users associated with this monitor
        $users = $monitor->users()->where('user_monitor.is_active', true)->get();

        Log::info('SendCustomMonitorNotification: Found users for monitor', [
            'monitor_id' => $monitor->id,
            'user_count' => $users->count(),
            'user_ids' => $users->pluck('id')->toArray(),
        ]);

        if ($users->isEmpty()) {
            Log::warning('SendCustomMonitorNotification: No active users found for monitor', [
                'monitor_id' => $monitor->id,
                'monitor_url' => $monitor->url,
            ]);

            return;
        }

        $status = $this->getStatusFromEvent($event);

        Log::info('SendCustomMonitorNotification: Sending notifications', [
            'monitor_id' => $monitor->id,
            'status' => $status,
            'user_count' => $users->count(),
        ]);

        // Send notification to all active users of this monitor
        foreach ($users as $user) {
            try {
                $user->notify(new MonitorStatusChanged([
                    'id' => $monitor->id,
                    'url' => $monitor->url,
                    'status' => $status,
                    'message' => "Website {$monitor->url} is {$status}",
                ]));

                Log::info('SendCustomMonitorNotification: Notification sent successfully', [
                    'monitor_id' => $monitor->id,
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
            } catch (\Exception $e) {
                Log::error('SendCustomMonitorNotification: Failed to send notification', [
                    'monitor_id' => $monitor->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('SendCustomMonitorNotification: Completed processing', [
            'monitor_id' => $monitor->id,
            'status' => $status,
            'total_users_processed' => $users->count(),
        ]);
    }

    /**
     * Log history record immediately when event is fired
     */
    private function logHistoryRecord(object $event, $monitor): void
    {
        try {
            $status = $this->getStatusFromEvent($event);

            // Prepare history data
            $historyData = [
                'uptime_status' => strtolower($status),
                'message' => $this->getMessageFromEvent($event, $monitor),
                'response_time_ms' => $this->getResponseTimeFromEvent($event),
                'certificate_status' => $monitor->certificate_status,
                'certificate_expiration_date' => $monitor->certificate_expiration_date,
            ];

            // Log to monitor's individual SQLite database
            $monitor->createHistoryRecord($historyData);

            Log::info('SendCustomMonitorNotification: History record logged', [
                'monitor_id' => $monitor->id,
                'status' => $status,
                'history_data' => $historyData,
            ]);

        } catch (\Exception $e) {
            Log::error('SendCustomMonitorNotification: Failed to log history record', [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get status from event
     */
    private function getStatusFromEvent(object $event): string
    {
        if ($event instanceof UptimeCheckFailed) {
            return 'DOWN';
        }

        if ($event instanceof UptimeCheckRecovered || $event instanceof UptimeCheckSucceeded) {
            return 'UP';
        }

        return 'UNKNOWN';
    }

    /**
     * Get message from event
     */
    private function getMessageFromEvent(object $event, $monitor): string
    {
        if ($event instanceof UptimeCheckFailed) {
            return $monitor->uptime_check_failure_reason ?? 'Website is down';
        }

        if ($event instanceof UptimeCheckRecovered) {
            return 'Website is back online';
        }

        if ($event instanceof UptimeCheckSucceeded) {
            return 'Website is online';
        }

        return 'Status check completed';
    }

    /**
     * Get response time from event if available
     */
    private function getResponseTimeFromEvent(object $event): ?int
    {
        // Try to get response time from event properties
        if (property_exists($event, 'responseTime') && $event->responseTime) {
            return $event->responseTime;
        }

        if (property_exists($event, 'response_time') && $event->response_time) {
            return $event->response_time;
        }

        return null;
    }
}
