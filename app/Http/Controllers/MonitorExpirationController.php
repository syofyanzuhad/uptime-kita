<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitorExpirationController extends Controller
{
    /**
     * Display monitors with domain expiration checking enabled,
     * sorted by soonest expiration date first.
     */
    public function index(Request $request): Response
    {
        $paginator = Monitor::query()
            ->where('domain_expiration_check_enabled', true)
            ->whereNotNull('domain_expiration_date')
            ->with(['tags'])
            ->orderBy('domain_expiration_date', 'asc')
            ->paginate(50)
            ->withQueryString();

        $monitors = $paginator->map(fn (Monitor $monitor) => [
            'id' => $monitor->id,
            'name' => $monitor->display_name ?: $monitor->raw_url,
            'url' => $monitor->raw_url,
            'host' => $monitor->host,
            'favicon' => $monitor->favicon,
            'uptime_status' => $monitor->uptime_status,
            'domain_expiration_check_enabled' => (bool) $monitor->domain_expiration_check_enabled,
            'domain_expiration_date' => $monitor->domain_expiration_date?->toISOString(),
            'domain_expiration_lookup_error' => $monitor->domain_expiration_lookup_error,
            'days_left' => $monitor->domain_expiration_date
                ? (int) now()->startOfDay()->diffInDays($monitor->domain_expiration_date->copy()->startOfDay(), false)
                : null,
            'tags' => $monitor->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name]),
        ]);

        return Inertia::render('monitors/Expiration', [
            'monitors' => [
                'data' => $monitors->values(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
            ],
            'stats' => [
                'total' => $paginator->total(),
                'expired' => Monitor::query()
                    ->where('domain_expiration_check_enabled', true)
                    ->where('domain_expiration_date', '<', now())
                    ->count(),
                'expiring_soon' => Monitor::query()
                    ->where('domain_expiration_check_enabled', true)
                    ->where('domain_expiration_date', '>=', now())
                    ->where('domain_expiration_date', '<=', now()->addDays(30))
                    ->count(),
            ],
        ]);
    }
}
