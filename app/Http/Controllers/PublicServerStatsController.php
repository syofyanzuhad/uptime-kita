<?php

namespace App\Http\Controllers;

use App\Services\ServerResourceService;
use Illuminate\Http\JsonResponse;

class PublicServerStatsController extends Controller
{
    public function __construct(
        protected ServerResourceService $serverResourceService
    ) {}

    /**
     * Get public server stats (limited data for transparency).
     */
    public function __invoke(): JsonResponse
    {
        // Check if public stats are enabled
        if (! config('app.show_public_server_stats', true)) {
            return response()->json(['enabled' => false]);
        }

        $metrics = $this->serverResourceService->getMetrics();

        // Return only limited, non-sensitive data
        return response()->json([
            'enabled' => true,
            'cpu_percent' => $metrics['cpu']['usage_percent'],
            'memory_percent' => $metrics['memory']['usage_percent'],
            'uptime' => $metrics['uptime']['formatted'],
            'uptime_seconds' => $metrics['uptime']['seconds'],
            'response_time' => $this->calculateResponseTime(),
            'timestamp' => $metrics['timestamp'],
        ]);
    }

    /**
     * Calculate a simple response time metric.
     */
    protected function calculateResponseTime(): int
    {
        $start = microtime(true);

        // Simple DB ping to measure response time
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            // Ignore connection errors
        }

        $end = microtime(true);

        return (int) round(($end - $start) * 1000); // Convert to milliseconds
    }
}
