<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorUptimeDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'date',
        'uptime_percentage',
        'avg_response_time',
        'min_response_time',
        'max_response_time',
        'total_checks',
        'failed_checks',
    ];

    protected $casts = [
        'date' => 'date',
        'uptime_percentage' => 'float',
        'avg_response_time' => 'float',
        'min_response_time' => 'float',
        'max_response_time' => 'float',
        'total_checks' => 'integer',
        'failed_checks' => 'integer',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
