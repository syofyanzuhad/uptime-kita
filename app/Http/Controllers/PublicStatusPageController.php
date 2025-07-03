<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\StatusPage;
use App\Http\Resources\StatusPageResource;

class PublicStatusPageController extends Controller
{
    /**
     * Display the public status page.
     */
    public function show(string $path): Response
    {
        $statusPage = StatusPage::with(['monitors.latestHistory'])->where('path', $path)->firstOrFail();
        $statusPageResource = new StatusPageResource($statusPage);

        return Inertia::render('StatusPages/Public', [
            // 'statusPage' => $statusPage,
            'statusPage' => $statusPageResource,
        ]);
    }
}
