<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Tags\Tag;

class MonitorExpirationController extends Controller
{
    /**
     * Display monitors with domain expiration checking enabled,
     * sorted by soonest expiration date first.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter', 'all');
        $uptimeFilter = $request->input('uptime_filter', 'all');
        $tagFilter = $request->input('tag_filter');
        $perPage = (int) $request->input('per_page', 50);

        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 50;
        }

        $baseQuery = Monitor::query()
            ->where('domain_expiration_check_enabled', true)
            ->whereNotNull('domain_expiration_date');

        // Base stats before search/filters are applied
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'expired' => (clone $baseQuery)
                ->where('domain_expiration_date', '<', now())
                ->count(),
            'expiring_soon' => (clone $baseQuery)
                ->where('domain_expiration_date', '>=', now())
                ->where('domain_expiration_date', '<=', now()->addDays(30))
                ->count(),
        ];

        $query = (clone $baseQuery)
            ->with(['tags'])
            ->search($search);

        if ($statusFilter === 'expired') {
            $query->where('domain_expiration_date', '<', now());
        } elseif ($statusFilter === 'expiring_soon') {
            $query->where('domain_expiration_date', '>=', now())
                ->where('domain_expiration_date', '<=', now()->addDays(30));
        } elseif ($statusFilter === 'healthy') {
            $query->where('domain_expiration_date', '>', now()->addDays(30));
        }

        if ($uptimeFilter === 'up' || $uptimeFilter === 'down') {
            $query->where('uptime_status', $uptimeFilter);
        }

        if ($tagFilter) {
            $query->withAnyTags([$tagFilter]);
        }

        $paginator = $query
            ->orderBy('domain_expiration_date', 'asc')
            ->paginate($perPage)
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

        $availableTags = Tag::whereIn('id', function ($query) {
            $query->select('tag_id')
                ->from('taggables')
                ->where('taggable_type', Monitor::class);
        })->orderBy('name')->get(['id', 'name']);

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
            'stats' => $stats,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'uptimeFilter' => $uptimeFilter,
            'tagFilter' => $tagFilter,
            'perPage' => $perPage,
            'availableTags' => $availableTags,
        ]);
    }
}
