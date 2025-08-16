<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorPerformanceHourly extends Model
{
    use HasFactory;

    protected $table = 'monitor_performance_hourly';

    protected $fillable = [
        'monitor_id',
        'hour',
        'avg_response_time',
        'p95_response_time',
        'p99_response_time',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'hour' => 'datetime',
        'avg_response_time' => 'float',
        'p95_response_time' => 'float',
        'p99_response_time' => 'float',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    /**
     * Get the monitor that owns the performance record.
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Get the uptime percentage for this hour.
     */
    public function getUptimePercentageAttribute(): float
    {
        $total = $this->success_count + $this->failure_count;

        if ($total === 0) {
            return 100.0;
        }

        return round(($this->success_count / $total) * 100, 2);
    }
}
