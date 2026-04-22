<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonitorResource;
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
        $monitors = cache()->remember('public_status_page_monitors_'.$path, 60, function () use ($path) {
            // Find the status page first
            $statusPage = StatusPage::where('path', $path)->first();

            if (! $statusPage) {
                return collect();
            }

            return $statusPage->monitors()
                ->where('uptime_check_enabled', true)
                ->with([
                    'uptimeDaily',
                    'tags',
                    'statistics',
                    // Optimization: We don't need full uptimesDaily here if we use pre-calculated stats
                    // But if the frontend needs the sparkline, we keep the last 7 days
                    'uptimesDaily' => function ($query) {
                        $query->where('date', '>=', now()->subDays(7)->toDateString())
                            ->orderBy('date', 'asc');
                    },
                ])
                ->orderBy('status_page_monitor.order')
                ->get();
        });
        // info($monitors);
        if ($monitors->isEmpty()) {
            return response()->json([
                'message' => 'No monitors found',
            ], 404);
        }

        return response()->json(
            MonitorResource::collection($monitors)
        );
    }
}
