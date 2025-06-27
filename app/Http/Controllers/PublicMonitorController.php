<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use App\Http\Resources\MonitorResource;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\MonitorCollection;

class PublicMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $authenticated = auth()->check();
        $page = $request->get('page', 1);
        $perPage = 12; // Number of monitors per page

        // Differentiate cache keys for authenticated and guest users, and also by page number
        $cacheKey = ($authenticated ? 'public_monitors_authenticated_' . auth()->id() : 'public_monitors_guest') . '_page_' . $page;

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage) {
            // Always only show public monitors
            return new MonitorCollection(
                Monitor::withoutGlobalScope('user')
                    ->with('users')
                    ->where('is_public', true)
                    ->paginate($perPage, ['*'], 'page', $page)
            );
        });

        return response()->json($publicMonitors);
    }
}
