<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Services\OgImageService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class OgImageController extends Controller
{
    public function __construct(
        private OgImageService $ogImageService
    ) {}

    /**
     * Generate OG image for public monitors list page.
     * Route: /og/monitors.png
     */
    public function monitorsIndex(): Response
    {
        $cacheKey = 'og_image_monitors_index';

        $image = Cache::remember($cacheKey, 300, function () {
            $stats = [
                'total' => Monitor::query()->where('is_public', true)->count(),
                'up' => Monitor::query()->where('is_public', true)->where('uptime_status', 'up')->count(),
                'down' => Monitor::query()->where('is_public', true)->where('uptime_status', 'down')->count(),
            ];

            return $this->ogImageService->generateMonitorsIndex($stats);
        });

        return $this->pngResponse($image);
    }

    /**
     * Generate OG image for individual monitor.
     * Route: /og/monitor/{domain}.png
     */
    public function monitor(string $domain): Response
    {
        $url = 'https://'.urldecode($domain);

        $monitor = Monitor::query()
            ->where('url', $url)
            ->where('is_public', true)
            ->with('statistics')
            ->first();

        if (! $monitor) {
            return $this->notFoundImage($domain);
        }

        $cacheKey = "og_image_monitor_{$monitor->id}";

        $image = Cache::remember($cacheKey, 300, function () use ($monitor) {
            return $this->ogImageService->generateMonitor($monitor);
        });

        return $this->pngResponse($image);
    }

    /**
     * Generate OG image for status page.
     * Route: /og/status/{path}.png
     */
    public function statusPage(string $path): Response
    {
        $statusPage = StatusPage::query()
            ->where('path', $path)
            ->with(['monitors' => function ($query) {
                $query->with('statistics');
            }])
            ->first();

        if (! $statusPage) {
            return $this->notFoundImage($path);
        }

        $cacheKey = "og_image_status_page_{$statusPage->id}";

        $image = Cache::remember($cacheKey, 300, function () use ($statusPage) {
            return $this->ogImageService->generateStatusPage($statusPage);
        });

        return $this->pngResponse($image);
    }

    /**
     * Return PNG response with proper headers.
     */
    private function pngResponse(string $imageData): Response
    {
        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=300, s-maxage=300',
            'Expires' => now()->addMinutes(5)->toRfc7231String(),
        ]);
    }

    /**
     * Generate and return not found image.
     */
    private function notFoundImage(string $identifier): Response
    {
        $image = $this->ogImageService->generateNotFound($identifier);

        return $this->pngResponse($image);
    }
}
