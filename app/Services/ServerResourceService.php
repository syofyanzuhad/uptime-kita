<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServerResourceService
{
    /**
     * Get all server resource metrics.
     */
    public function getMetrics(): array
    {
        return [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'uptime' => $this->getServerUptime(),
            'load_average' => $this->getLoadAverage(),
            'php' => $this->getPhpInfo(),
            'laravel' => $this->getLaravelInfo(),
            'database' => $this->getDatabaseInfo(),
            'queue' => $this->getQueueInfo(),
            'cache' => $this->getCacheInfo(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get CPU usage percentage.
     */
    protected function getCpuUsage(): array
    {
        $cpuUsage = 0;
        $cores = 1;

        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS
            $output = shell_exec("top -l 1 | grep 'CPU usage' | awk '{print $3}' | sed 's/%//'");
            $cpuUsage = $output ? (float) trim($output) : 0;
            $cores = (int) shell_exec('sysctl -n hw.ncpu 2>/dev/null') ?: 1;
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux - using /proc/stat
            $stat1 = file_get_contents('/proc/stat');
            usleep(100000); // 100ms
            $stat2 = file_get_contents('/proc/stat');

            $info1 = $this->parseProcStat($stat1);
            $info2 = $this->parseProcStat($stat2);

            $diff = [
                'user' => $info2['user'] - $info1['user'],
                'nice' => $info2['nice'] - $info1['nice'],
                'system' => $info2['system'] - $info1['system'],
                'idle' => $info2['idle'] - $info1['idle'],
            ];

            $total = array_sum($diff);
            $cpuUsage = $total > 0 ? round(($total - $diff['idle']) / $total * 100, 1) : 0;

            $cores = (int) shell_exec('nproc 2>/dev/null') ?: 1;
        }

        return [
            'usage_percent' => round($cpuUsage, 1),
            'cores' => $cores,
        ];
    }

    /**
     * Parse /proc/stat for Linux CPU info.
     */
    protected function parseProcStat(string $stat): array
    {
        $lines = explode("\n", $stat);
        $cpuLine = $lines[0];
        $parts = preg_split('/\s+/', $cpuLine);

        return [
            'user' => (int) ($parts[1] ?? 0),
            'nice' => (int) ($parts[2] ?? 0),
            'system' => (int) ($parts[3] ?? 0),
            'idle' => (int) ($parts[4] ?? 0),
        ];
    }

    /**
     * Get memory usage information.
     */
    protected function getMemoryUsage(): array
    {
        $total = 0;
        $used = 0;
        $free = 0;

        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS
            $pageSize = (int) shell_exec('pagesize 2>/dev/null') ?: 4096;
            $vmStat = shell_exec('vm_stat 2>/dev/null');

            if ($vmStat) {
                preg_match('/Pages free:\s+(\d+)/', $vmStat, $freePages);
                preg_match('/Pages active:\s+(\d+)/', $vmStat, $activePages);
                preg_match('/Pages inactive:\s+(\d+)/', $vmStat, $inactivePages);
                preg_match('/Pages speculative:\s+(\d+)/', $vmStat, $speculativePages);
                preg_match('/Pages wired down:\s+(\d+)/', $vmStat, $wiredPages);

                $freePageCount = (int) ($freePages[1] ?? 0);
                $activePageCount = (int) ($activePages[1] ?? 0);
                $inactivePageCount = (int) ($inactivePages[1] ?? 0);
                $speculativePageCount = (int) ($speculativePages[1] ?? 0);
                $wiredPageCount = (int) ($wiredPages[1] ?? 0);

                $totalPages = $freePageCount + $activePageCount + $inactivePageCount + $speculativePageCount + $wiredPageCount;
                $total = $totalPages * $pageSize;
                $free = ($freePageCount + $inactivePageCount) * $pageSize;
                $used = $total - $free;
            }

            // Fallback to sysctl
            if ($total === 0) {
                $total = (int) shell_exec('sysctl -n hw.memsize 2>/dev/null') ?: 0;
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux - using /proc/meminfo
            $meminfo = file_get_contents('/proc/meminfo');

            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatch);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $availableMatch);

            $total = ((int) ($totalMatch[1] ?? 0)) * 1024; // Convert from KB to bytes
            $available = ((int) ($availableMatch[1] ?? 0)) * 1024;
            $used = $total - $available;
            $free = $available;
        }

        $usagePercent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage_percent' => $usagePercent,
            'total_formatted' => $this->formatBytes($total),
            'used_formatted' => $this->formatBytes($used),
            'free_formatted' => $this->formatBytes($free),
        ];
    }

    /**
     * Get disk usage information.
     */
    protected function getDiskUsage(): array
    {
        $path = base_path();
        $total = disk_total_space($path) ?: 0;
        $free = disk_free_space($path) ?: 0;
        $used = $total - $free;

        $usagePercent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'usage_percent' => $usagePercent,
            'total_formatted' => $this->formatBytes($total),
            'used_formatted' => $this->formatBytes($used),
            'free_formatted' => $this->formatBytes($free),
            'path' => $path,
        ];
    }

    /**
     * Get server uptime.
     */
    protected function getServerUptime(): array
    {
        $uptimeSeconds = 0;

        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS
            $bootTime = shell_exec("sysctl -n kern.boottime | awk '{print $4}' | sed 's/,//'");
            if ($bootTime) {
                $uptimeSeconds = time() - (int) trim($bootTime);
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux
            $uptime = file_get_contents('/proc/uptime');
            if ($uptime) {
                $uptimeSeconds = (int) explode(' ', $uptime)[0];
            }
        }

        return [
            'seconds' => $uptimeSeconds,
            'formatted' => $this->formatUptime($uptimeSeconds),
        ];
    }

    /**
     * Get load average (1, 5, 15 minutes).
     */
    protected function getLoadAverage(): array
    {
        $load = sys_getloadavg();

        return [
            '1min' => round($load[0] ?? 0, 2),
            '5min' => round($load[1] ?? 0, 2),
            '15min' => round($load[2] ?? 0, 2),
        ];
    }

    /**
     * Get PHP information.
     */
    protected function getPhpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => $this->getLoadedExtensions(),
            'current_memory' => memory_get_usage(true),
            'current_memory_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => memory_get_peak_usage(true),
            'peak_memory_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Get important loaded PHP extensions.
     */
    protected function getLoadedExtensions(): array
    {
        $important = ['pdo', 'pdo_sqlite', 'pdo_mysql', 'curl', 'mbstring', 'openssl', 'json', 'xml', 'zip', 'gd', 'redis', 'pcntl'];
        $loaded = get_loaded_extensions();

        $result = [];
        foreach ($important as $ext) {
            $result[$ext] = in_array($ext, $loaded);
        }

        return $result;
    }

    /**
     * Get Laravel information.
     */
    protected function getLaravelInfo(): array
    {
        return [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }

    /**
     * Get database information.
     */
    protected function getDatabaseInfo(): array
    {
        $connection = config('database.default');
        $size = 0;

        try {
            if ($connection === 'sqlite') {
                $path = config('database.connections.sqlite.database');
                if (file_exists($path)) {
                    $size = filesize($path);
                }
            } elseif ($connection === 'mysql') {
                $dbName = config('database.connections.mysql.database');
                $result = DB::select('SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
                $size = $result[0]->size ?? 0;
            }

            $status = 'connected';
        } catch (\Exception $e) {
            $status = 'error';
        }

        return [
            'connection' => $connection,
            'status' => $status,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
        ];
    }

    /**
     * Get queue information.
     */
    protected function getQueueInfo(): array
    {
        $driver = config('queue.default');
        $pending = 0;
        $failed = 0;

        try {
            // Count pending jobs
            if ($driver === 'database') {
                $pending = DB::table('jobs')->count();
            }

            // Count failed jobs
            $failed = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            // Tables may not exist
        }

        return [
            'driver' => $driver,
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
        ];
    }

    /**
     * Get cache information.
     */
    protected function getCacheInfo(): array
    {
        $driver = config('cache.default');
        $status = 'unknown';

        try {
            $testKey = 'server_resource_test_'.uniqid();
            Cache::put($testKey, true, 10);
            $status = Cache::get($testKey) === true ? 'working' : 'error';
            Cache::forget($testKey);
        } catch (\Exception $e) {
            $status = 'error';
        }

        return [
            'driver' => $driver,
            'status' => $status,
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $index = min((int) floor($base), count($units) - 1);

        return round(pow(1024, $base - floor($base)), $precision).' '.$units[$index];
    }

    /**
     * Format uptime seconds to human readable format.
     */
    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.'d';
        }
        if ($hours > 0) {
            $parts[] = $hours.'h';
        }
        if ($minutes > 0) {
            $parts[] = $minutes.'m';
        }

        return empty($parts) ? '0m' : implode(' ', $parts);
    }
}
