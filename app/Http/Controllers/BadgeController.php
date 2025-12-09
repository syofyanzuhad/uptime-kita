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
     * Generate SVG badge using shields.io compatible format.
     */
    private function generateBadge(string $label, string $value, string $color, string $style): string
    {
        // For "for-the-badge" style, use uppercase and different sizing
        $isForTheBadge = $style === 'for-the-badge';
        $displayLabel = $isForTheBadge ? strtoupper($label) : $label;
        $displayValue = $isForTheBadge ? strtoupper($value) : $value;

        // Calculate widths - more generous padding for readability
        $charWidth = $isForTheBadge ? 7.5 : 6.5;
        $padding = $isForTheBadge ? 14 : 12;
        $labelWidth = (int) ceil(strlen($displayLabel) * $charWidth + $padding);
        $valueWidth = (int) ceil(strlen($displayValue) * $charWidth + $padding);
        $totalWidth = $labelWidth + $valueWidth;

        $height = $isForTheBadge ? 28 : 20;
        $fontSize = $isForTheBadge ? 90 : 110;
        $textY = $isForTheBadge ? 170 : 140;
        $shadowY = $isForTheBadge ? 180 : 150;

        $borderRadius = match ($style) {
            'flat-square' => 0,
            'for-the-badge' => 4,
            default => 3,
        };

        $gradient = $style === 'plastic' ? $this->getGradientDef() : '';

        // Calculate center positions (multiply by 10 for SVG coordinate system with scale 0.1)
        $labelCenterX = ($labelWidth / 2) * 10;
        $valueCenterX = ($labelWidth + $valueWidth / 2) * 10;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$totalWidth}" height="{$height}" role="img" aria-label="{$label}: {$value}">
  <title>{$label}: {$value}</title>
  {$gradient}
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <clipPath id="r">
    <rect width="{$totalWidth}" height="{$height}" rx="{$borderRadius}" fill="#fff"/>
  </clipPath>
  <g clip-path="url(#r)">
    <rect width="{$labelWidth}" height="{$height}" fill="#555"/>
    <rect x="{$labelWidth}" width="{$valueWidth}" height="{$height}" fill="{$color}"/>
    <rect width="{$totalWidth}" height="{$height}" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" text-rendering="geometricPrecision" font-size="{$fontSize}">
    <text aria-hidden="true" x="{$labelCenterX}" y="{$shadowY}" fill="#010101" fill-opacity=".3" transform="scale(.1)">{$displayLabel}</text>
    <text x="{$labelCenterX}" y="{$textY}" transform="scale(.1)" fill="#fff">{$displayLabel}</text>
    <text aria-hidden="true" x="{$valueCenterX}" y="{$shadowY}" fill="#010101" fill-opacity=".3" transform="scale(.1)">{$displayValue}</text>
    <text x="{$valueCenterX}" y="{$textY}" transform="scale(.1)" fill="#fff">{$displayValue}</text>
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
