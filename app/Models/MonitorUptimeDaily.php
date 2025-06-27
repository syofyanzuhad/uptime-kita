<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorUptimeDaily extends Model
{
    protected $fillable = [
        'monitor_id',
        'date',
        'uptime_percentage',
    ];

    protected $casts = [
        'date' => 'date',
        'uptime_percentage' => 'float',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
