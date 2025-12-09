<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BadgeController extends Controller
{
    /**
     * Display the uptime badge for a monitor.
     */
    public function show(Request $request, string $domain): Response
    {
        $period = $request->get('period', '24h');
        $style = $request->get('style', 'flat');
        $label = $request->get('label', 'uptime');

        // Build the full HTTPS URL
        $url = 'https://'.urldecode($domain);

        // Find the monitor
        $monitor = Monitor::where('url', $url)
            ->where('is_public', true)
            ->where('uptime_check_enabled', true)
            ->with('statistics')
            ->first();

        if (! $monitor) {
            return $this->svgResponse(
                $this->generateBadge($label, 'not found', '#9f9f9f', $style)
            );
        }

        // Get uptime based on period
        $uptime = match ($period) {
            '7d' => $monitor->statistics?->uptime_7d,
            '30d' => $monitor->statistics?->uptime_30d,
            '90d' => $monitor->statistics?->uptime_90d,
            default => $monitor->statistics?->uptime_24h,
        } ?? 100;

        $color = $this->getColorForUptime($uptime);
        $value = number_format($uptime, 1).'%';

        return $this->svgResponse(
            $this->generateBadge($label, $value, $color, $style)
        );
    }

    /**
     * Get the color based on uptime percentage.
     */
    private function getColorForUptime(float $uptime): string
    {
        return match (true) {
            $uptime >= 99 => '#4c1',      // brightgreen
            $uptime >= 97 => '#97ca00',   // green
            $uptime >= 95 => '#a4a61d',   // yellowgreen
            $uptime >= 90 => '#dfb317',   // yellow
            $uptime >= 80 => '#fe7d37',   // orange
            default => '#e05d44',          // red
        };
    }

    /**
     * Generate SVG badge.
     */
    private function generateBadge(string $label, string $value, string $color, string $style): string
    {
        // Calculate widths based on text length (approximate)
        $labelWidth = max(strlen($label) * 6.5 + 10, 40);
        $valueWidth = max(strlen($value) * 7 + 10, 40);
        $totalWidth = $labelWidth + $valueWidth;

        $borderRadius = match ($style) {
            'flat-square' => 0,
            default => 3,
        };

        $gradient = $style === 'plastic' ? $this->getGradientDef() : '';
        $gradientFill = $style === 'plastic' ? 'url(#gradient)' : '';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="{$totalWidth}" height="20" role="img" aria-label="{$label}: {$value}">
  <title>{$label}: {$value}</title>
  {$gradient}
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <clipPath id="r">
    <rect width="{$totalWidth}" height="20" rx="{$borderRadius}" fill="#fff"/>
  </clipPath>
  <g clip-path="url(#r)">
    <rect width="{$labelWidth}" height="20" fill="#555"/>
    <rect x="{$labelWidth}" width="{$valueWidth}" height="20" fill="{$color}"/>
    <rect width="{$totalWidth}" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" text-rendering="geometricPrecision" font-size="110">
    <text aria-hidden="true" x="{$this->getCenterX($labelWidth)}0" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$this->getTextLength($label)}0">{$label}</text>
    <text x="{$this->getCenterX($labelWidth)}0" y="140" transform="scale(.1)" fill="#fff" textLength="{$this->getTextLength($label)}0">{$label}</text>
    <text aria-hidden="true" x="{$this->getValueCenterX($labelWidth, $valueWidth)}0" y="150" fill="#010101" fill-opacity=".3" transform="scale(.1)" textLength="{$this->getTextLength($value)}0">{$value}</text>
    <text x="{$this->getValueCenterX($labelWidth, $valueWidth)}0" y="140" transform="scale(.1)" fill="#fff" textLength="{$this->getTextLength($value)}0">{$value}</text>
  </g>
</svg>
SVG;
    }

    /**
     * Get gradient definition for plastic style.
     */
    private function getGradientDef(): string
    {
        return <<<'GRADIENT'
  <linearGradient id="gradient" x2="0" y2="100%">
    <stop offset="0" stop-color="#fff" stop-opacity=".7"/>
    <stop offset=".1" stop-color="#aaa" stop-opacity=".1"/>
    <stop offset=".9" stop-opacity=".3"/>
    <stop offset="1" stop-opacity=".5"/>
  </linearGradient>
GRADIENT;
    }

    /**
     * Calculate center X position for label.
     */
    private function getCenterX(float $labelWidth): int
    {
        return (int) ($labelWidth / 2);
    }

    /**
     * Calculate center X position for value.
     */
    private function getValueCenterX(float $labelWidth, float $valueWidth): int
    {
        return (int) ($labelWidth + $valueWidth / 2);
    }

    /**
     * Calculate text length for SVG.
     */
    private function getTextLength(string $text): int
    {
        return (int) (strlen($text) * 60);
    }

    /**
     * Return SVG response with proper headers.
     */
    private function svgResponse(string $svg): Response
    {
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=300, s-maxage=300',
            'Expires' => now()->addMinutes(5)->toRfc7231String(),
        ]);
    }
}
