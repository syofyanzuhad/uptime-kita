<?php

namespace App\Http\Controllers;

use App\Jobs\CheckMonitorBatchJob;
use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ManualMonitorCheckController extends Controller
{
    /**
     * Dispatch an immediate uptime check for a public monitor.
     *
     * Throttled to 3 requests per minute per IP (via route definition).
     * Additionally enforces a per-monitor cooldown via cache to prevent
     * multiple visitors from re-queuing the same monitor simultaneously.
     */
    public function __invoke(Request $request, string $domain): JsonResponse
    {
        $url = 'https://'.$domain;

        $monitor = Monitor::withoutGlobalScope('user')
            ->where('url', $url)
            ->where('is_public', true)
            ->where('uptime_check_enabled', true)
            ->first();

        if (! $monitor) {
            return response()->json(['message' => 'Monitor not found.'], 404);
        }

        $cooldownKey = "manual_check_cooldown_{$monitor->id}";
        $cooldownSeconds = 60;

        // Atomically acquire cooldown lock to prevent race conditions from concurrent clicks.
        $acquired = Cache::add($cooldownKey, true, $cooldownSeconds);

        if (! $acquired) {
            $remaining = (int) Cache::get($cooldownKey.'_ttl', $cooldownSeconds);

            return response()->json([
                'message' => 'A check was recently requested. Please wait before trying again.',
                'retry_after' => $remaining,
            ], 429);
        }

        // Store the TTL reference for countdown calculation
        Cache::put($cooldownKey.'_ttl', $cooldownSeconds, $cooldownSeconds);

        // Bust the cached history so the next page load reflects fresh data.
        Cache::forget("public_monitor_{$monitor->id}_100m_histories");

        CheckMonitorBatchJob::dispatch([$monitor->id]);

        return response()->json([
            'message' => 'Uptime check queued. Results will appear shortly.',
            'retry_after' => $cooldownSeconds,
        ], 202);
    }
}
