<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorCollection;
use App\Models\Monitor;
use Illuminate\Http\Request;

class PrivateMonitorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $page = $request->input('page', 1);
        $search = $request->input('search');

        // Build cache key based on search query
        $cacheKey = 'private_monitors_page_'.auth()->id().'_'.$page;
        if ($search) {
            $cacheKey .= '_search_'.md5($search);
        }

        $monitors = cache()->remember($cacheKey, 60, function () use ($search) {
            $query = Monitor::private()
                ->with(['users:id', 'uptimeDaily'])
                ->orderBy('created_at', 'desc');

            // Apply search filter if provided
            if ($search && strlen($search) >= 3) {
                $query->search($search);
            }

            return new MonitorCollection($query->paginate(12));
        });

        return response()->json($monitors);
    }
}
