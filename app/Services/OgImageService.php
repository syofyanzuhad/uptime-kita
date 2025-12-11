<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\StatusPage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

class OgImageService
{
    private ImageManager $manager;

    private int $width = 1200;

    private int $height = 630;

    // Brand colors matching BadgeController
    private array $colors = [
        'brightgreen' => '#22c55e',
        'green' => '#84cc16',
        'yellowgreen' => '#a3e635',
        'yellow' => '#facc15',
        'orange' => '#f97316',
        'red' => '#ef4444',
        'gray' => '#9ca3af',
        'background' => '#0f172a',     // Dark slate
        'card' => '#1e293b',           // Lighter slate
        'text' => '#f8fafc',           // Almost white
        'text_muted' => '#94a3b8',     // Muted gray
        'brand_blue' => '#3b82f6',     // Blue-500
    ];

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Generate OG image for monitors index page.
     */
    public function generateMonitorsIndex(array $stats): string
    {
        $image = $this->createBaseImage();

        // Add gradient background
        $this->addGradientOverlay($image);

        // Add logo (centered at top)
        $this->addLogo($image, $this->width / 2, 100, 0.8);

        // Add title
        $image->text('Public Monitors', $this->width / 2, 230, function (FontFactory $font) {
            $font->filename($this->getFont('bold'));
            $font->size(56);
            $font->color($this->colors['text']);
            $font->align('center');
            $font->valign('middle');
        });

        // Add subtitle
        $image->text('Real-time uptime monitoring for your services', $this->width / 2, 290, function (FontFactory $font) {
            $font->filename($this->getFont('regular'));
            $font->size(24);
            $font->color($this->colors['text_muted']);
            $font->align('center');
            $font->valign('middle');
        });

        // Add stats cards
        $this->drawStatsRow($image, [
            ['label' => 'Total Monitors', 'value' => (string) ($stats['total'] ?? 0), 'color' => $this->colors['brand_blue']],
            ['label' => 'Online', 'value' => (string) ($stats['up'] ?? 0), 'color' => $this->colors['brightgreen']],
            ['label' => 'Down', 'value' => (string) ($stats['down'] ?? 0), 'color' => $this->colors['red']],
        ], 370);

        // Add branding footer
        $this->addBranding($image);

        return $image->toPng()->toString();
    }

    /**
     * Generate OG image for individual monitor.
     */
    public function generateMonitor(Monitor $monitor): string
    {
        $image = $this->createBaseImage();

        // Add gradient background
        $this->addGradientOverlay($image);

        // Add logo (smaller, top-left corner)
        $this->addLogo($image, 100, 60, 0.4);

        // Status indicator (large circle)
        $statusColor = $monitor->uptime_status === 'up'
            ? $this->colors['brightgreen']
            : $this->colors['red'];

        $centerX = $this->width / 2;

        // Draw status circle background
        $image->drawCircle($centerX, 200, function ($circle) use ($statusColor) {
            $circle->radius(70);
            $circle->background($statusColor);
        });

        // Status text inside circle
        $statusText = $monitor->uptime_status === 'up' ? 'UP' : 'DOWN';
        $image->text($statusText, $centerX, 200, function (FontFactory $font) {
            $font->filename($this->getFont('bold'));
            $font->size(36);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        // Monitor host/domain
        $host = $monitor->host ?? parse_url($monitor->url, PHP_URL_HOST) ?? $monitor->url;
        $displayHost = strlen($host) > 40 ? substr($host, 0, 37).'...' : $host;

        $image->text($displayHost, $centerX, 320, function (FontFactory $font) {
            $font->filename($this->getFont('bold'));
            $font->size(42);
            $font->color($this->colors['text']);
            $font->align('center');
            $font->valign('middle');
        });

        // Uptime stats
        $uptime24h = $monitor->statistics?->uptime_24h ?? 100;
        $uptime7d = $monitor->statistics?->uptime_7d ?? 100;
        $uptime30d = $monitor->statistics?->uptime_30d ?? 100;

        $this->drawStatsRow($image, [
            ['label' => '24h Uptime', 'value' => number_format($uptime24h, 1).'%', 'color' => $this->getColorForUptime($uptime24h)],
            ['label' => '7d Uptime', 'value' => number_format($uptime7d, 1).'%', 'color' => $this->getColorForUptime($uptime7d)],
            ['label' => '30d Uptime', 'value' => number_format($uptime30d, 1).'%', 'color' => $this->getColorForUptime($uptime30d)],
        ], 400);

        // Response time (if available)
        $avgResponseTime = $monitor->statistics?->avg_response_time_24h;
        if ($avgResponseTime) {
            $image->text('Avg Response: '.round($avgResponseTime).'ms', $centerX, 540, function (FontFactory $font) {
                $font->filename($this->getFont('regular'));
                $font->size(22);
                $font->color($this->colors['text_muted']);
                $font->align('center');
                $font->valign('middle');
            });
        }

        $this->addBranding($image);

        return $image->toPng()->toString();
    }

    /**
     * Generate OG image for status page.
     */
    public function generateStatusPage(StatusPage $statusPage): string
    {
        $image = $this->createBaseImage();

        // Add gradient background
        $this->addGradientOverlay($image);

        // Add logo (smaller, top-left)
        $this->addLogo($image, 100, 60, 0.4);

        // Calculate overall status
        $monitors = $statusPage->monitors;
        $totalMonitors = $monitors->count();
        $upMonitors = $monitors->where('uptime_status', 'up')->count();
        $allUp = $totalMonitors > 0 && $upMonitors === $totalMonitors;

        // Overall status indicator
        $statusColor = $allUp ? $this->colors['brightgreen'] : $this->colors['orange'];
        $statusText = $allUp ? 'All Systems Operational' : 'Partial Outage';

        if ($totalMonitors > 0 && $upMonitors === 0) {
            $statusColor = $this->colors['red'];
            $statusText = 'Major Outage';
        }

        // Status banner
        $bannerY = 150;
        $bannerHeight = 70;
        $image->drawRectangle(100, $bannerY, function ($rectangle) use ($statusColor, $bannerHeight) {
            $rectangle->size(1000, $bannerHeight);
            $rectangle->background($statusColor);
        });

        $image->text($statusText, $this->width / 2, $bannerY + ($bannerHeight / 2), function (FontFactory $font) {
            $font->filename($this->getFont('bold'));
            $font->size(32);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        // Status page title
        $title = strlen($statusPage->title) > 40 ? substr($statusPage->title, 0, 37).'...' : $statusPage->title;
        $image->text($title, $this->width / 2, 280, function (FontFactory $font) {
            $font->filename($this->getFont('bold'));
            $font->size(48);
            $font->color($this->colors['text']);
            $font->align('center');
            $font->valign('middle');
        });

        // Description (truncated)
        if ($statusPage->description) {
            $description = strlen($statusPage->description) > 70
                ? substr($statusPage->description, 0, 67).'...'
                : $statusPage->description;

            $image->text($description, $this->width / 2, 340, function (FontFactory $font) {
                $font->filename($this->getFont('regular'));
                $font->size(22);
                $font->color($this->colors['text_muted']);
                $font->align('center');
                $font->valign('middle');
            });
        }

        // Monitor stats
        $this->drawStatsRow($image, [
            ['label' => 'Total Services', 'value' => (string) $totalMonitors, 'color' => $this->colors['brand_blue']],
            ['label' => 'Online', 'value' => (string) $upMonitors, 'color' => $this->colors['brightgreen']],
            ['label' => 'Issues', 'value' => (string) ($totalMonitors - $upMonitors), 'color' => $this->colors['orange']],
        ], 420);

        $this->addBranding($image);

        return $image->toPng()->toString();
    }

    /**
     * Generate not found image.
     */
    public function generateNotFound(string $identifier): string
    {
        $image = $this->createBaseImage();

        // Add gradient background
        $this->addGradientOverlay($image);

        $this->addLogo($image, $this->width / 2, 180, 0.8);

        $image->text('Not Found', $this->width / 2, 340, function (FontFactory $font) {
            $font->filename($this->getFont('bold'));
            $font->size(56);
            $font->color($this->colors['red']);
            $font->align('center');
            $font->valign('middle');
        });

        $displayId = strlen($identifier) > 50 ? substr($identifier, 0, 47).'...' : $identifier;
        $image->text($displayId, $this->width / 2, 420, function (FontFactory $font) {
            $font->filename($this->getFont('regular'));
            $font->size(28);
            $font->color($this->colors['text_muted']);
            $font->align('center');
            $font->valign('middle');
        });

        $this->addBranding($image);

        return $image->toPng()->toString();
    }

    /**
     * Create base image with background color.
     */
    private function createBaseImage(): ImageInterface
    {
        return $this->manager->create($this->width, $this->height)
            ->fill($this->colors['background']);
    }

    /**
     * Add a subtle gradient overlay for visual interest.
     */
    private function addGradientOverlay(ImageInterface $image): void
    {
        // Add a subtle lighter area at the top
        $image->drawRectangle(0, 0, function ($rectangle) {
            $rectangle->size($this->width, 200);
            $rectangle->background('rgba(30, 41, 59, 0.5)');
        });
    }

    /**
     * Add the logo to the image.
     */
    private function addLogo(ImageInterface $image, int $x, int $y, float $scale = 1.0): void
    {
        $logoPath = public_path('images/uptime-kita.png');

        if (! file_exists($logoPath)) {
            $logoPath = public_path('images/uptime-kita.jpg');
        }

        if (file_exists($logoPath)) {
            $logo = $this->manager->read($logoPath);
            $logoWidth = (int) (150 * $scale);
            $logoHeight = (int) (150 * $scale);
            $logo->resize($logoWidth, $logoHeight);

            // Calculate position to center the logo at (x, y)
            $posX = (int) ($x - ($logoWidth / 2));
            $posY = (int) ($y - ($logoHeight / 2));

            $image->place($logo, 'top-left', $posX, $posY);
        } else {
            // If no logo, draw a simple placeholder
            $image->text('Uptime Kita', $x, $y, function (FontFactory $font) {
                $font->filename($this->getFont('bold'));
                $font->size(36);
                $font->color($this->colors['brand_blue']);
                $font->align('center');
                $font->valign('middle');
            });
        }
    }

    /**
     * Add branding footer.
     */
    private function addBranding(ImageInterface $image): void
    {
        $image->text('uptime.syofyanzuhad.dev', $this->width / 2, 595, function (FontFactory $font) {
            $font->filename($this->getFont('regular'));
            $font->size(18);
            $font->color($this->colors['text_muted']);
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Draw a row of stats cards.
     */
    private function drawStatsRow(ImageInterface $image, array $stats, int $y): void
    {
        $cardWidth = 300;
        $cardHeight = 100;
        $spacing = 50;
        $totalWidth = (count($stats) * $cardWidth) + ((count($stats) - 1) * $spacing);
        $startX = ($this->width - $totalWidth) / 2;

        foreach ($stats as $index => $stat) {
            $cardX = (int) ($startX + ($index * ($cardWidth + $spacing)));
            $centerX = $cardX + ($cardWidth / 2);

            // Card background with rounded corners
            $image->drawRectangle($cardX, $y, function ($rectangle) use ($cardWidth, $cardHeight) {
                $rectangle->size($cardWidth, $cardHeight);
                $rectangle->background($this->colors['card']);
            });

            // Value
            $image->text($stat['value'], (int) $centerX, $y + 35, function (FontFactory $font) use ($stat) {
                $font->filename($this->getFont('bold'));
                $font->size(36);
                $font->color($stat['color']);
                $font->align('center');
                $font->valign('middle');
            });

            // Label
            $image->text($stat['label'], (int) $centerX, $y + 75, function (FontFactory $font) {
                $font->filename($this->getFont('regular'));
                $font->size(16);
                $font->color($this->colors['text_muted']);
                $font->align('center');
                $font->valign('middle');
            });
        }
    }

    /**
     * Get color based on uptime percentage.
     */
    private function getColorForUptime(float $uptime): string
    {
        return match (true) {
            $uptime >= 99 => $this->colors['brightgreen'],
            $uptime >= 97 => $this->colors['green'],
            $uptime >= 95 => $this->colors['yellowgreen'],
            $uptime >= 90 => $this->colors['yellow'],
            $uptime >= 80 => $this->colors['orange'],
            default => $this->colors['red'],
        };
    }

    /**
     * Get font path.
     */
    private function getFont(string $weight): string
    {
        $fontPath = match ($weight) {
            'bold' => resource_path('fonts/Inter-Bold.ttf'),
            default => resource_path('fonts/Inter-Regular.ttf'),
        };

        // Fallback to a system font if custom font doesn't exist
        if (! file_exists($fontPath)) {
            // Try common system font paths
            $systemFonts = [
                '/System/Library/Fonts/Helvetica.ttc',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            ];

            foreach ($systemFonts as $systemFont) {
                if (file_exists($systemFont)) {
                    return $systemFont;
                }
            }
        }

        return $fontPath;
    }
}
