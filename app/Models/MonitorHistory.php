<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class MonitorHistory extends Model
{
    use Prunable;

    protected $fillable = [
        'monitor_id',
        'uptime_status',
        'message',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function scopeLatestByMonitorId($query, $monitorId)
    {
        return $query->where('monitor_id', $monitorId)
                     ->latest()
                     ->first();
    }
    public function scopeLatestByMonitorIds($query, $monitorIds)
    {
        return $query->whereIn('monitor_id', $monitorIds)
                     ->latest()
                     ->get();
    }

    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}
