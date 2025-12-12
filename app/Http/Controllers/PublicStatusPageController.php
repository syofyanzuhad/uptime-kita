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
        // Check if this request is from a custom domain
        $customDomainStatusPage = request()->attributes->get('custom_domain_status_page');

        if ($customDomainStatusPage) {
            $statusPage = $customDomainStatusPage;
        } else {
            $cacheKey = 'public_status_page_'.$path;
            $statusPage = cache()->remember($cacheKey, 60, function () use ($path) {
                return StatusPage::where('path', $path)->firstOrFail();
            });
        }

        $statusPageResource = new StatusPageResource($statusPage);

        $appUrl = config('app.url');
        $title = $statusPage->title ?? 'Status Page';
        $description = $statusPage->description ?? "View the current status of {$title} services.";

        return Inertia::render('status-pages/Public', [
            'statusPage' => $statusPageResource,
            'isAuthenticated' => auth()->check(),
            'isCustomDomain' => $customDomainStatusPage !== null,
        ])->withViewData([
            'ogTitle' => "{$title} - Uptime Kita",
            'ogDescription' => $description,
            'ogImage' => "{$appUrl}/og/status/{$path}.png",
            'ogUrl' => "{$appUrl}/status/{$path}",
        ]);
    }

    /**
     * Return monitors for a public status page as JSON.
     */
    public function monitors(string $path)
    {
        $monitors = cache()->remember('public_status_page_monitors_'.$path, 60, function () {
            return StatusPageMonitor::with(['monitor'])
                ->whereHas('statusPage', function ($query) {
                    $query->where('path', request()->route('path'));
                })
                ->orderBy('order')
                ->get()
                ->map(function ($statusPageMonitor) {
                    return $statusPageMonitor->monitor;
                })
                ->filter(function ($monitor) {
                    // only return if monitor is not null
                    return $monitor !== null;
                });
        });
        // info($monitors);
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
