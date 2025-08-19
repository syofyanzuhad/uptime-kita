<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'uptime_1h',
        'uptime_24h',
        'uptime_7d',
        'uptime_30d',
        'uptime_90d',
        'avg_response_time_24h',
        'min_response_time_24h',
        'max_response_time_24h',
        'incidents_24h',
        'incidents_7d',
        'incidents_30d',
        'total_checks_24h',
        'total_checks_7d',
        'total_checks_30d',
        'recent_history_100m',
        'calculated_at',
    ];

    protected $casts = [
        'uptime_1h' => 'decimal:2',
        'uptime_24h' => 'decimal:2',
        'uptime_7d' => 'decimal:2',
        'uptime_30d' => 'decimal:2',
        'uptime_90d' => 'decimal:2',
        'recent_history_100m' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Get uptime stats in the format expected by the frontend
     */
    public function getUptimeStatsAttribute(): array
    {
        return [
            '24h' => $this->uptime_24h,
            '7d' => $this->uptime_7d,
            '30d' => $this->uptime_30d,
            '90d' => $this->uptime_90d,
        ];
    }

    /**
     * Get response time stats in the format expected by the frontend
     */
    public function getResponseTimeStatsAttribute(): array
    {
        return [
            'average' => $this->avg_response_time_24h,
            'min' => $this->min_response_time_24h,
            'max' => $this->max_response_time_24h,
        ];
    }

    /**
     * Check if statistics are fresh (calculated within the last hour)
     */
    public function isFresh(): bool
    {
        return $this->calculated_at && $this->calculated_at->gt(now()->subHour());
    }
}
