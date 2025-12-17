<?php

namespace App\Listeners;

use App\Models\MonitorIncident;
use App\Notifications\MonitorStatusChanged;
use App\Services\AlertPatternService;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;

class SendCustomMonitorNotification
{
    public function __construct(
        protected AlertPatternService $alertPatternService
    ) {}

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

        // Skip notification if monitor is in maintenance window
        if ($monitor->isInMaintenance()) {
            Log::info('SendCustomMonitorNotification: Skipping notification - monitor is in maintenance window', [
                'monitor_id' => $monitor->id,
                'monitor_url' => (string) $monitor->url,
                'maintenance_ends_at' => $monitor->maintenance_ends_at,
            ]);

            return;
        }

        $isDownEvent = $event instanceof UptimeCheckFailed;
        $isRecoveryEvent = $event instanceof UptimeCheckRecovered;

        // Check if we should send notification based on alert pattern
        if ($isDownEvent) {
            if (! $this->alertPatternService->shouldSendDownAlert($monitor)) {
                Log::info('SendCustomMonitorNotification: Skipping - not a Fibonacci alert point', [
                    'monitor_id' => $monitor->id,
                    'failure_count' => $monitor->uptime_check_times_failed_in_a_row,
                    'next_alert_at' => $this->alertPatternService->getNextAlertAt(
                        $monitor->uptime_check_times_failed_in_a_row
                    ),
                ]);

                return;
            }
        }

        if ($isRecoveryEvent) {
            $incident = $this->findRecentIncident($monitor);

            if (! $this->alertPatternService->shouldSendRecoveryAlert($monitor, $incident)) {
                Log::info('SendCustomMonitorNotification: Skipping recovery - no DOWN alert was sent', [
                    'monitor_id' => $monitor->id,
                    'incident_id' => $incident?->id,
                ]);

                return;
            }
        }

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

        $status = $isDownEvent ? 'DOWN' : 'UP';

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
                    'url' => (string) $monitor->url,
                    'status' => $status,
                    'message' => "Website {$monitor->url} is {$status}",
                    'is_public' => $monitor->is_public,
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

        // After successfully sending DOWN notifications, update the incident
        if ($isDownEvent) {
            $this->markIncidentAlertSent($monitor);
        }

        Log::info('SendCustomMonitorNotification: Completed processing', [
            'monitor_id' => $monitor->id,
            'status' => $status,
            'total_users_processed' => $users->count(),
        ]);
    }

    /**
     * Find the most recent incident for a monitor (ongoing or just ended).
     */
    protected function findRecentIncident($monitor): ?MonitorIncident
    {
        return MonitorIncident::where('monitor_id', $monitor->id)
            ->where(function ($query) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', now()->subMinutes(5));
            })
            ->orderBy('started_at', 'desc')
            ->first();
    }

    /**
     * Mark the current incident as having sent an alert.
     */
    protected function markIncidentAlertSent($monitor): void
    {
        $incident = MonitorIncident::where('monitor_id', $monitor->id)
            ->whereNull('ended_at')
            ->first();

        if ($incident) {
            $incident->update([
                'down_alert_sent' => true,
                'last_alert_at_failure_count' => $monitor->uptime_check_times_failed_in_a_row,
            ]);

            Log::info('SendCustomMonitorNotification: Marked incident alert sent', [
                'monitor_id' => $monitor->id,
                'incident_id' => $incident->id,
                'failure_count' => $monitor->uptime_check_times_failed_in_a_row,
            ]);
        }
    }
}
