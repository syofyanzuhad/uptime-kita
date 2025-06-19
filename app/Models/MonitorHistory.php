<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorHistory extends Model
{
    protected $fillable = [
        'monitor_id',
        'uptime_status',
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
}
