<?php

namespace App\Http\Controllers;

use App\Models\TelemetryPing;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TelemetryDashboardController extends Controller
{
    /**
     * Display the telemetry dashboard.
     */
    public function index(): Response|JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Only administrators can access telemetry dashboard.');
        }

        // Check if receiver is enabled
        if (! config('telemetry.receiver_enabled')) {
            return Inertia::render('admin/TelemetryDashboard', [
                'receiverEnabled' => false,
                'statistics' => null,
                'versionDistribution' => null,
                'osDistribution' => null,
                'growthData' => null,
                'recentPings' => [],
            ]);
        }

        return Inertia::render('admin/TelemetryDashboard', [
            'receiverEnabled' => true,
            'statistics' => TelemetryPing::getStatistics(),
            'versionDistribution' => TelemetryPing::getVersionDistribution(),
            'osDistribution' => TelemetryPing::getOsDistribution(),
            'growthData' => TelemetryPing::getGrowthData(12),
            'recentPings' => TelemetryPing::query()
                ->orderByDesc('last_ping_at')
                ->limit(20)
                ->get()
                ->map(fn ($ping) => [
                    'id' => $ping->id,
                    'instance_id' => substr($ping->instance_id, 0, 8).'...',
                    'app_version' => $ping->app_version,
                    'php_version' => $ping->php_version,
                    'laravel_version' => $ping->laravel_version,
                    'monitors_total' => $ping->monitors_total,
                    'users_total' => $ping->users_total,
                    'os_type' => $ping->os_type,
                    'first_seen_at' => $ping->first_seen_at?->format('Y-m-d'),
                    'last_ping_at' => $ping->last_ping_at?->diffForHumans(),
                    'ping_count' => $ping->ping_count,
                ]),
        ]);
    }

    /**
     * Get updated statistics as JSON (for polling).
     */
    public function stats(): JsonResponse
    {
        if (! auth()->user()?->is_admin) {
            return response()->json(['error' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        if (! config('telemetry.receiver_enabled')) {
            return response()->json(['error' => 'Receiver not enabled'], 400);
        }

        return response()->json([
            'statistics' => TelemetryPing::getStatistics(),
            'versionDistribution' => TelemetryPing::getVersionDistribution(),
            'osDistribution' => TelemetryPing::getOsDistribution(),
        ]);
    }
}
