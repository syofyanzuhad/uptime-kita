<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
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

        return Inertia::render('StatusPages/Public', [
            'statusPage' => $statusPageResource,
        ]);
    }

    /**
     * Return monitors for a public status page as JSON.
     */
    public function monitors(string $path)
    {
        $statusPage = StatusPage::where('path', $path)->firstOrFail();
        $monitors = cache()->remember('public_status_page_monitors_' . $path, 60, function () use ($statusPage) {
            return $statusPage->monitors()
                ->with(['latestHistory', 'uptimesDaily'])
                ->get();
        });
        return response()->json(
            \App\Http\Resources\MonitorResource::collection($monitors)
        );
    }
}
