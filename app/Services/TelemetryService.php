<?php

namespace App\Services;

use App\Models\Monitor;
use Illuminate\Support\Facades\DB;

class TelemetryService
{
    public function __construct(
        protected InstanceIdService $instanceIdService
    ) {}

    /**
     * Check if telemetry is enabled.
     */
    public function isEnabled(): bool
    {
        return config('telemetry.enabled', false);
    }

    /**
     * Get the telemetry endpoint URL.
     */
    public function getEndpoint(): string
    {
        return config('telemetry.endpoint');
    }

    /**
     * Collect all telemetry data.
     *
     * IMPORTANT: Only non-identifying, aggregate data is collected.
     * No URLs, user emails, IP addresses, or other personal data.
     */
    public function collectData(): array
    {
        return [
            // Anonymous instance identifier
            'instance_id' => $this->instanceIdService->getInstanceId(),

            // Version information
            'versions' => $this->getVersionInfo(),

            // Aggregate statistics (counts only)
            'stats' => $this->getAggregateStats(),

            // System information
            'system' => $this->getSystemInfo(),

            // Ping metadata
            'ping' => [
                'timestamp' => now()->toIso8601String(),
                'timezone' => config('app.timezone'),
            ],
        ];
    }

    /**
     * Get version information.
     */
    protected function getVersionInfo(): array
    {
        return [
            'app' => config('app.last_update', 'unknown'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
        ];
    }

    /**
     * Get aggregate statistics (counts only, no identifying data).
     */
    protected function getAggregateStats(): array
    {
        // Use withoutGlobalScopes to get accurate counts
        $monitorCount = Monitor::withoutGlobalScopes()->count();
        $publicMonitorCount = Monitor::withoutGlobalScopes()
            ->where('is_public', true)
            ->count();
        $userCount = DB::table('users')->count();
        $statusPageCount = DB::table('status_pages')->count();

        return [
            'monitors_total' => $monitorCount,
            'monitors_public' => $publicMonitorCount,
            'users_total' => $userCount,
            'status_pages_total' => $statusPageCount,
            'install_date' => $this->instanceIdService->getInstallDate(),
        ];
    }

    /**
     * Get non-identifying system information.
     */
    protected function getSystemInfo(): array
    {
        return [
            'os_family' => PHP_OS_FAMILY, // 'Linux', 'Darwin', 'Windows'
            'os_type' => $this->getOsType(),
            'database_driver' => config('database.default'),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * Get a simplified OS type string.
     */
    protected function getOsType(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => 'macOS',
            'Windows' => 'Windows',
            'Linux' => $this->detectLinuxDistro(),
            default => 'Other',
        };
    }

    /**
     * Detect Linux distribution (generic categorization only).
     */
    protected function detectLinuxDistro(): string
    {
        if (! is_readable('/etc/os-release')) {
            return 'Linux';
        }

        $content = file_get_contents('/etc/os-release');

        // Only detect broad categories, not specific versions
        if (str_contains($content, 'Ubuntu')) {
            return 'Ubuntu';
        }
        if (str_contains($content, 'Debian')) {
            return 'Debian';
        }
        if (str_contains($content, 'Alpine')) {
            return 'Alpine';
        }
        if (str_contains($content, 'CentOS') || str_contains($content, 'Red Hat')) {
            return 'RHEL-based';
        }

        return 'Linux';
    }

    /**
     * Get the current telemetry settings for display.
     */
    public function getSettings(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'endpoint' => $this->getEndpoint(),
            'frequency' => config('telemetry.frequency'),
            'instance_id' => $this->instanceIdService->getInstanceId(),
            'install_date' => $this->instanceIdService->getInstallDate(),
            'last_ping' => $this->getLastPingTime(),
            'debug' => config('telemetry.debug'),
        ];
    }

    /**
     * Get the last ping timestamp from cache.
     */
    public function getLastPingTime(): ?string
    {
        return cache()->get('telemetry:last_ping');
    }

    /**
     * Record a successful ping.
     */
    public function recordPing(): void
    {
        cache()->forever('telemetry:last_ping', now()->toIso8601String());
    }

    /**
     * Preview the telemetry data that would be sent.
     * Useful for transparency in the admin UI.
     */
    public function previewData(): array
    {
        return $this->collectData();
    }
}
