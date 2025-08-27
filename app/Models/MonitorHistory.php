<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class MonitorHistory extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'monitor_id',
        'uptime_status',
        'message',
        'response_time',
        'status_code',
        'checked_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'response_time' => 'integer',
        'status_code' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function scopeLatestByMonitorId($query, $monitorId)
    {
        return $query->where('monitor_id', $monitorId)
            ->latest();
    }

    public function scopeLatestByMonitorIds($query, $monitorIds)
    {
        return $query->whereIn('monitor_id', $monitorIds)
            ->latest();
    }

    /**
     * Get unique history records per minute for a specific monitor
     * Returns only one record per monitor per minute (the latest one)
     */
    public static function getUniquePerMinute($monitorId, $limit = null, $orderBy = 'created_at', $orderDirection = 'desc')
    {
        $sql = "
            SELECT id FROM (
                SELECT id, created_at, ROW_NUMBER() OVER (
                    PARTITION BY monitor_id, strftime('%Y-%m-%d %H:%M', created_at) 
                    ORDER BY created_at DESC, id DESC
                ) as rn
                FROM monitor_histories
                WHERE monitor_id = ?
            ) ranked
            WHERE rn = 1
            ORDER BY {$orderBy} {$orderDirection}
        ";

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        $uniqueIds = \DB::select($sql, [$monitorId]);
        $ids = array_column($uniqueIds, 'id');

        return static::whereIn('id', $ids)->orderBy($orderBy, $orderDirection);
    }

    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}
