<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use Inertia\Inertia;
use Inertia\Response;

class PublicStatusPageController extends Controller
{
    /**
     * Display the public status page.
     */
    public function show(string $path): Response
    {
        $cacheKey = 'public_status_page_' . $path;
        $statusPageResource = cache()->remember($cacheKey, 60, function () use ($path) {
            $statusPage = StatusPage::with(['monitors.latestHistory', 'monitors.uptimesDaily'])->where('path', $path)->firstOrFail();
            return new StatusPageResource($statusPage);
        });

        return Inertia::render('StatusPages/Public', [
            'statusPage' => $statusPageResource,
        ]);
    }
}
