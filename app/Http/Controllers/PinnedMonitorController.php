<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use Illuminate\Http\Request;

class PinnedMonitorController extends Controller
{
    /**
     * Get all pinned monitors for the authenticated user.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $statusFilter = $request->input('status_filter', 'all');

        // Build cache key based on search query and status filter
        $cacheKey = 'pinned_monitors_page_'.auth()->id().'_'.$page;
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }
        if ($statusFilter !== 'all') {
            $cacheKey .= '_filter_'.$statusFilter;
        }

        $monitors = cache()->remember($cacheKey, 60, function () use ($search, $statusFilter) {
            $baseQuery = Monitor::withoutGlobalScope('user')
                ->whereHas('users', function ($query) {
                    $query->where('user_monitor.user_id', auth()->id())
                        ->where('user_monitor.is_pinned', true);
                })
                ->with(['users:id', 'uptimeDaily']);

            if ($statusFilter === 'disabled') {
                // User-specific disabled: monitors where user has is_active = false
                $query = $baseQuery->whereHas('users', function ($query) {
                    $query->where('user_id', auth()->id());
                    $query->where('user_monitor.is_active', false);
                })
                    ->orderBy('created_at', 'desc');
            } elseif ($statusFilter === 'globally_enabled') {
                $query = $baseQuery->withoutGlobalScope('enabled')
                    ->where('uptime_check_enabled', true)
                    ->orderBy('created_at', 'desc');
            } elseif ($statusFilter === 'globally_disabled') {
                $query = $baseQuery->withoutGlobalScope('enabled')
                    ->where('uptime_check_enabled', false)
                    ->orderBy('created_at', 'desc');
            } else {
                $query = $baseQuery->orderBy('created_at', 'desc');
                if ($statusFilter === 'up' || $statusFilter === 'down') {
                    $query->where('uptime_status', $statusFilter);
                }
            }

            // Apply search filter if provided
            if ($search && strlen($search) >= 3) {
                $query->where(function ($q) use ($search) {
                    $q->where('url', 'like', "%$search%")
                        ->orWhereRaw('REPLACE(REPLACE(url, "https://", ""), "http://", "") LIKE ?', ["%$search%"]);
                });
            }

            return new MonitorCollection($query->paginate(12));
        });

        return response()->json($monitors);
    }

    /**
     * Toggle pin status for a monitor.
     */
    public function toggle(Request $request, $monitorId)
    {
        // Validate the request
        $request->validate([
            'is_pinned' => 'required|boolean',
        ]);

        // Find the monitor without the user scope
        $monitor = Monitor::withoutGlobalScope('user')->find($monitorId);
        
        if (!$monitor) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monitor not found.',
                ], 404);
            }
            abort(404);
        }
        
        $user = auth()->user();

        // Get the pivot record directly from the database
        $pivotRecord = $user->monitors()
            ->wherePivot('monitor_id', $monitor->id)
            ->withPivot('is_pinned', 'is_active')
            ->first();

        $isPinned = $request->input('is_pinned');

        if (! $pivotRecord) {
            // User is not subscribed to this monitor
            if ($isPinned) {
                // If trying to pin any monitor they're not subscribed to
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You must be subscribed to this monitor to pin it.',
                    ], 403);
                }
                return back()->with('flash', [
                    'type' => 'error',
                    'message' => 'You must be subscribed to this monitor to pin it.',
                ]);
            }
            // If unpinning a monitor they're not subscribed to, just return success
            $newPinnedStatus = false;
        } else {
            // Update the pinned status
            $user->monitors()->updateExistingPivot($monitor->id, [
                'is_pinned' => $isPinned,
            ]);
            $newPinnedStatus = $isPinned;
        }

        // Clear related caches
        $userId = auth()->id();
        cache()->forget("is_pinned_{$monitor->id}_{$userId}");

        // Clear monitor listing caches
        $cacheKeys = [
            "pinned_monitors_page_{$userId}_1",
            "private_monitors_page_{$userId}_1",
            "public_monitors_authenticated_{$userId}_1",
            "public_monitors_authenticated_{$userId}", // Also clear this cache variant
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }

        // Return appropriate response
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_pinned' => $newPinnedStatus,
                'message' => $newPinnedStatus ? 'Monitor pinned successfully' : 'Monitor unpinned successfully',
            ]);
        }

        return back()->with('flash', [
            'type' => 'success',
            'message' => $newPinnedStatus ? 'Monitor pinned successfully' : 'Monitor unpinned successfully',
        ]);
    }
}
