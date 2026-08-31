<?php

namespace App\Listeners;

use App\Notifications\StatusPageIncidentNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;

class NotifyStatusPageSubscribersOnStatusChange
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if (! ($event instanceof UptimeCheckFailed || $event instanceof UptimeCheckRecovered)) {
            return;
        }

        $monitor = $event->monitor;

        // Skip notification if monitor is in maintenance window
        if (method_exists($monitor, 'isInMaintenance') && $monitor->isInMaintenance()) {
            return;
        }

        $statusPages = $monitor->statusPages()->get();

        if ($statusPages->isEmpty()) {
            return;
        }

        $status = $event instanceof UptimeCheckFailed ? 'down' : 'up';
        $reason = $event instanceof UptimeCheckFailed ? $monitor->uptime_check_failure_reason : null;

        foreach ($statusPages as $statusPage) {
            $subscribers = $statusPage->subscribers()->verified()->get();

            foreach ($subscribers as $subscriber) {
                Notification::route('mail', $subscriber->email)
                    ->notify(new StatusPageIncidentNotification($subscriber, $monitor, $status, $reason));
            }
        }
    }
}
