<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusPageMonitorResource;
use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use Illuminate\Http\JsonResponse;
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
    public function monitors(string $path): JsonResponse
    {
        $monitors = cache()->remember('public_status_page_monitors_'.$path, 60, function () use ($path) {
            // Find the status page first
            $statusPage = StatusPage::where('path', $path)->first();

            if (! $statusPage) {
                return collect();
            }

            return $statusPage->monitors()
                ->where('uptime_check_enabled', true)
                ->select([
                    'monitors.id',
                    'monitors.url',
                    'monitors.display_name',
                    'monitors.uptime_status',
                    'monitors.uptime_last_check_date',
                    'monitors.uptime_check_enabled',
                    'monitors.certificate_check_enabled',
                    'monitors.certificate_status',
                    'monitors.domain_expiration_check_enabled',
                    'monitors.domain_expiration_date',
                    'status_page_monitor.order',
                ])
                ->with([
                    'uptimesDaily' => function ($query) {
                        $query->select(['monitor_id', 'date', 'uptime_percentage'])
                            ->where('date', '>=', now()->subDays(90)->toDateString())
                            ->orderBy('date', 'asc');
                    },
                ])
                ->orderBy('status_page_monitor.order')
                ->get();
        });
        if ($monitors->isEmpty()) {
            return response()->json([]);
        }

        return response()->json(
            StatusPageMonitorResource::collection($monitors)
        );
    }
}
