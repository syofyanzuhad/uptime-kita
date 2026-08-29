<?php

namespace App\Listeners;

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

        // Skip notification if monitor is in maintenance window
        if ($monitor->isInMaintenance()) {
            return;
        }

        // Get all users associated with this monitor
        $users = $monitor->users()->where('user_monitor.is_active', true)->get();

        if ($users->isEmpty()) {
            return;
        }

        $status = $event instanceof UptimeCheckFailed ? 'DOWN' : 'UP';

        // Buffer notification data in cache for batching
        $cacheKey = 'pending_monitor_notifications';
        $lockKey = 'lock_monitor_notifications';

        // Use cache locking to safely update the shared list
        cache()->lock($lockKey, 10)->block(5, function () use ($cacheKey, $monitor, $status, $users) {
            $pending = cache()->get($cacheKey, []);

            $pending[] = [
                'monitor_id' => $monitor->id,
                'url' => (string) $monitor->url,
                'status' => $status,
                'user_ids' => $users->pluck('id')->toArray(),
                'timestamp' => now()->timestamp,
            ];

            cache()->put($cacheKey, $pending, now()->addMinutes(10));
        });
    }
}
