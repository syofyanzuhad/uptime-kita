<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitorListController extends Controller
{
    /**
     * Display dynamic monitor listing based on type.
     */
    public function index(Request $request, string $type)
    {
        // Validate the type parameter
        if (! in_array($type, ['pinned', 'private', 'public'])) {
            abort(404);
        }

        $page = $request->input('page', 1);
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter', 'all');
        $visibilityFilter = $request->input('visibility_filter', 'all');
        $tagFilter = $request->input('tag_filter');
        $perPage = $request->input('per_page', 12);

        // Build the base query based on type
        $query = $this->buildQuery($type, $search, $statusFilter, $visibilityFilter, $tagFilter);

        // Paginate results
        $monitors = $query->paginate($perPage);

        // Return Inertia view
        return Inertia::render('monitors/List', [
            'monitors' => new MonitorCollection($monitors),
            'type' => $type,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'visibilityFilter' => $visibilityFilter,
            'tagFilter' => $tagFilter,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Build the query based on monitor type and filters.
     */
    private function buildQuery(string $type, $search, $statusFilter, $visibilityFilter, $tagFilter)
    {
        $baseQuery = Monitor::withoutGlobalScope('user');

        switch ($type) {
            case 'pinned':
                $query = $baseQuery->whereHas('users', function ($q) {
                    $q->where('user_monitor.user_id', auth()->id())
                        ->where('user_monitor.is_pinned', true);
                });
                break;

            case 'private':
                $query = $baseQuery->where('is_public', false)
                    ->whereHas('users', function ($q) {
                        $q->where('user_monitor.user_id', auth()->id());
                    });
                break;

            case 'public':
                $query = $baseQuery->where('is_public', true);
                // If user is authenticated, include subscription status
                if (auth()->check()) {
                    $query->with(['users' => function ($q) {
                        $q->where('users.id', auth()->id());
                    }]);
                }
                break;

            default:
                $query = $baseQuery;
        }

        // Add common relationships
        $query->with(['users:id', 'uptimeDaily']);

        // Apply status filter
        if ($statusFilter === 'disabled') {
            $query->whereHas('users', function ($q) {
                $q->where('user_id', auth()->id())
                    ->where('user_monitor.is_active', false);
            });
        } elseif ($statusFilter === 'globally_enabled') {
            $query->withoutGlobalScope('enabled')
                ->where('uptime_check_enabled', true);
        } elseif ($statusFilter === 'globally_disabled') {
            $query->withoutGlobalScope('enabled')
                ->where('uptime_check_enabled', false);
        } elseif (in_array($statusFilter, ['up', 'down'])) {
            $query->where('uptime_status', $statusFilter);
        }

        // Apply visibility filter (only for non-type-specific views)
        if ($visibilityFilter !== 'all' && ! in_array($type, ['private', 'public'])) {
            if ($visibilityFilter === 'public') {
                $query->where('is_public', true);
            } elseif ($visibilityFilter === 'private') {
                $query->where('is_public', false);
            }
        }

        // Apply search filter
        if ($search && strlen($search) >= 3) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%$search%")
                    ->orWhereRaw('REPLACE(REPLACE(url, "https://", ""), "http://", "") LIKE ?', ["%$search%"]);
            });
        }

        // Apply tag filter if provided
        if ($tagFilter) {
            $query->whereHas('tags', function ($q) use ($tagFilter) {
                $q->where('tags.id', $tagFilter);
            });
        }

        // Order by creation date
        $query->orderBy('created_at', 'desc');

        return $query;
    }
}
