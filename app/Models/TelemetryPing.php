<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelemetryPing extends Model
{
    protected $fillable = [
        'instance_id',
        'app_version',
        'php_version',
        'laravel_version',
        'monitors_total',
        'monitors_public',
        'users_total',
        'status_pages_total',
        'os_family',
        'os_type',
        'database_driver',
        'queue_driver',
        'cache_driver',
        'install_date',
        'first_seen_at',
        'last_ping_at',
        'ping_count',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'install_date' => 'date',
            'first_seen_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'ping_count' => 'integer',
            'monitors_total' => 'integer',
            'monitors_public' => 'integer',
            'users_total' => 'integer',
            'status_pages_total' => 'integer',
            'raw_data' => 'array',
        ];
    }

    /**
     * Scope to get active instances (pinged within last N days).
     */
    public function scopeActive($query, int $days = 7)
    {
        return $query->where('last_ping_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get instances first seen within date range.
     */
    public function scopeFirstSeenBetween($query, $start, $end)
    {
        return $query->whereBetween('first_seen_at', [$start, $end]);
    }

    /**
     * Get statistics for dashboard.
     */
    public static function getStatistics(): array
    {
        $total = self::count();
        $activeLastWeek = self::active(7)->count();
        $activeLastMonth = self::active(30)->count();
        $newThisMonth = self::firstSeenBetween(now()->startOfMonth(), now())->count();
        $newLastMonth = self::firstSeenBetween(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth())->count();

        return [
            'total_instances' => $total,
            'active_last_7_days' => $activeLastWeek,
            'active_last_30_days' => $activeLastMonth,
            'new_this_month' => $newThisMonth,
            'new_last_month' => $newLastMonth,
        ];
    }

    /**
     * Get version distribution.
     */
    public static function getVersionDistribution(): array
    {
        return [
            'app' => self::query()
                ->selectRaw('app_version, COUNT(*) as count')
                ->whereNotNull('app_version')
                ->groupBy('app_version')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'app_version')
                ->toArray(),
            'php' => self::query()
                ->selectRaw('php_version, COUNT(*) as count')
                ->whereNotNull('php_version')
                ->groupBy('php_version')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'php_version')
                ->toArray(),
            'laravel' => self::query()
                ->selectRaw('laravel_version, COUNT(*) as count')
                ->whereNotNull('laravel_version')
                ->groupBy('laravel_version')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'laravel_version')
                ->toArray(),
        ];
    }

    /**
     * Get OS distribution.
     */
    public static function getOsDistribution(): array
    {
        return self::query()
            ->selectRaw('os_type, COUNT(*) as count')
            ->whereNotNull('os_type')
            ->groupBy('os_type')
            ->orderByDesc('count')
            ->pluck('count', 'os_type')
            ->toArray();
    }

    /**
     * Get growth data for chart.
     */
    public static function getGrowthData(int $months = 12): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = self::where('first_seen_at', '<=', $date->endOfMonth())->count();
            $data[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

        return $data;
    }
}
