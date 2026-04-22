<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;

class SendCustomMonitorNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'default';

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

        Log::info('SendCustomMonitorNotification: Event buffered for batching', [
            'monitor_id' => $monitor->id,
            'status' => $status,
            'user_count' => $users->count(),
        ]);
    }
}
