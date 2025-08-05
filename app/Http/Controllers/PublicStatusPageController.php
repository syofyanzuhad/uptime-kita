<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use App\Models\StatusPageMonitor;
use Inertia\Inertia;
use Inertia\Response;

class PublicStatusPageController extends Controller
{
    /**
     * Display the public status page (without monitors).
     */
    public function show(string $path): Response
    {
        $cacheKey = 'public_status_page_' . $path;
        $statusPageResource = cache()->remember($cacheKey, 60, function () use ($path) {
            $statusPage = StatusPage::where('path', $path)->firstOrFail();
            return new StatusPageResource($statusPage);
        });

        return Inertia::render('status-pages/Public', [
            'statusPage' => $statusPageResource,
            'isAuthenticated' => auth()->check(),
        ]);
    }

    /**
     * Return monitors for a public status page as JSON.
     */
    public function monitors(string $path)
    {
        $monitors = cache()->remember('public_status_page_monitors_' . $path, 60, function () {
            return StatusPageMonitor::with(['monitor'])
                ->whereHas('statusPage', function ($query) {
                    $query->where('path', request()->route('path'));
                })
                ->orderBy('order')
                ->get()
                ->map(function ($statusPageMonitor) {
                    return $statusPageMonitor->monitor;
                });
        });
        info($monitors);
        if ($monitors->isEmpty()) {
            return response()->json([
                'message' => 'No monitors found',
            ], 404);
        }
        return response()->json(
            \App\Http\Resources\MonitorResource::collection($monitors)
        );
    }
}
