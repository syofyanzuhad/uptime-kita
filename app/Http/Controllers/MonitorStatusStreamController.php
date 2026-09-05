<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MonitorStatusStreamController extends Controller
{
    /**
     * Stream monitor status changes via Server-Sent Events (SSE).
     *
     * Query parameters:
     * - monitor_ids: comma-separated list of monitor IDs to watch (optional)
     * - status_page_id: watch all monitors on a specific status page (optional)
     * - last_event_id: ID of last received event for resuming (optional)
     */
    public function __invoke(Request $request): Response
    {
        if (! config('uptime-monitor.sse.enabled', true)) {
            abort(503, 'SSE streaming is disabled.');
        }

        $monitorIds = $request->query('monitor_ids')
            ? array_map('intval', explode(',', $request->query('monitor_ids')))
            : [];
        $statusPageId = $request->query('status_page_id')
            ? (int) $request->query('status_page_id')
            : null;
        $lastEventId = $request->header('Last-Event-ID') ?? $request->query('last_event_id');

        $heartbeatInterval = (int) config('uptime-monitor.sse.heartbeat_interval', 30);
        $maxDuration = (int) config('uptime-monitor.sse.max_duration', 300);
        $pollSleepMicroseconds = (int) config('uptime-monitor.sse.poll_sleep_microseconds', 500000);

        return response()->eventStream(function () use ($monitorIds, $statusPageId, $lastEventId, $heartbeatInterval, $maxDuration, $pollSleepMicroseconds) {
            $seenIds = $lastEventId ? [$lastEventId] : [];
            $lastHeartbeat = time();
            $startTime = time();

            while (true) {
                // Check max duration
                if ((time() - $startTime) > $maxDuration) {
                    yield new StreamedEvent(
                        event: 'reconnect',
                        data: json_encode(['reason' => 'max_duration']),
                    );
                    break;
                }

                // Get status changes from cache
                $changes = Cache::get('monitor_status_changes', []);

                foreach ($changes as $change) {
                    // Skip already seen events
                    if (in_array($change['id'], $seenIds)) {
                        continue;
                    }

                    // Filter by monitor IDs if specified
                    if (! empty($monitorIds) && ! in_array($change['monitor_id'], $monitorIds)) {
                        continue;
                    }

                    // Filter by status page if specified
                    if ($statusPageId && ! in_array($statusPageId, $change['status_page_ids'] ?? [])) {
                        continue;
                    }

                    $seenIds[] = $change['id'];

                    yield new StreamedEvent(
                        event: 'status_change',
                        data: json_encode($change),
                    );
                }

                // Send heartbeat every 30 seconds to keep connection alive
                if ((time() - $lastHeartbeat) >= $heartbeatInterval) {
                    yield new StreamedEvent(
                        event: 'heartbeat',
                        data: json_encode(['time' => now()->toIso8601String()]),
                    );
                    $lastHeartbeat = time();
                }

                // Sleep to prevent CPU spinning
                usleep($pollSleepMicroseconds);
            }
        }, endStreamWith: new StreamedEvent(event: 'end', data: '</stream>'));
    }
}
