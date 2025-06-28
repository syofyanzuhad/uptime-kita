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
        $perPage = 50; // Number of monitors per page
        $search = $request->get('search');
        if ($search && mb_strlen($search) < 3) {
            $search = null;
        }

        // Differentiate cache keys for authenticated and guest users, and also by page number
        $cacheKey = ($authenticated ? 'public_monitors_authenticated_' . auth()->id() : 'public_monitors_guest') . '_page_' . $page;
        if ($search) {
            $cacheKey .= '_search_' . md5($search);
        }

        $publicMonitors = cache()->remember($cacheKey, 60, function () use ($page, $perPage, $search) {
            // Always only show public monitors
            $query = Monitor::withoutGlobalScope('user')
                ->with(['users:id', 'uptimeDaily'])
                ->public();
            if ($search) {
                $query->search($search);
            }
            return new MonitorCollection(
                $query->paginate($perPage, ['*'], 'page', $page)
            );
        });

        return response()->json($publicMonitors);
    }
}
