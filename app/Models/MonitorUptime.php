<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorUptime extends Model
{
    protected $fillable = [
        'monitor_id',
        'unit',
        'uptime_percentage',
    ];

    protected $casts = [
        'uptime_percentage' => 'float',
    ];

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
    public function scopeDaily($query)
    {
        return $query->where('unit', 'DAILY');
    }
    public function scopeWeekly($query)
    {
        return $query->where('unit', 'WEEKLY');
    }
    public function scopeMonthly($query)
    {
        return $query->where('unit', 'MONTHLY');
    }
    public function scopeYearly($query)
    {
        return $query->where('unit', 'YEARLY');
    }
}
