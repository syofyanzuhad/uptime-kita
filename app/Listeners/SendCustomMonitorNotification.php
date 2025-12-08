<?php

namespace App\Listeners;

use App\Notifications\MonitorStatusChanged;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;

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

        // Skip notification if monitor is in maintenance window
        if ($monitor->isInMaintenance()) {
            Log::info('SendCustomMonitorNotification: Skipping notification - monitor is in maintenance window', [
                'monitor_id' => $monitor->id,
                'monitor_url' => (string) $monitor->url,
                'maintenance_ends_at' => $monitor->maintenance_ends_at,
            ]);

            return;
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

        $status = $event instanceof UptimeCheckFailed ? 'DOWN' : 'UP';

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

        Log::info('SendCustomMonitorNotification: Completed processing', [
            'monitor_id' => $monitor->id,
            'status' => $status,
            'total_users_processed' => $users->count(),
        ]);
    }
}
