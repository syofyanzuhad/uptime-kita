<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;

class BroadcastMonitorStatusChange
{
    /**
     * Handle the event.
     *
     * Broadcasts monitor status changes to the cache for SSE consumers.
     * Only broadcasts for public monitors.
     */
    public function handle(object $event): void
    {
        $monitor = $event->monitor;

        // Only broadcast for public monitors
        if (! $monitor->is_public) {
            return;
        }

        $oldStatus = $event instanceof UptimeCheckRecovered ? 'down' : 'up';
        $newStatus = $event instanceof UptimeCheckFailed ? 'down' : 'up';

        // Skip if status hasn't actually changed
        if ($oldStatus === $newStatus) {
            return;
        }

        $statusChange = [
            'id' => uniqid('msc_'),
            'monitor_id' => $monitor->id,
            'monitor_name' => $monitor->display_name ?? $monitor->url->getHost(),
            'monitor_url' => (string) $monitor->url,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_at' => now()->toIso8601String(),
            'favicon' => $monitor->favicon,
            'status_page_ids' => $monitor->statusPages()->pluck('status_pages.id')->toArray(),
        ];

        Log::info('BroadcastMonitorStatusChange: Broadcasting status change', [
            'monitor_id' => $monitor->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // Store in cache with TTL of 5 minutes
        $cacheKey = 'monitor_status_changes';
        $changes = Cache::get($cacheKey, []);

        // Add new change
        $changes[] = $statusChange;

        // Keep only changes within last 5 minutes and max 100 entries
        $fiveMinutesAgo = now()->subMinutes(5);
        $changes = collect($changes)
            ->filter(fn ($c) => \Carbon\Carbon::parse($c['changed_at'])->isAfter($fiveMinutesAgo))
            ->take(100)
            ->values()
            ->toArray();

        Cache::put($cacheKey, $changes, now()->addMinutes(5));
    }
}
