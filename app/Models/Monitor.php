<?php

namespace App\Models;

use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Spatie\UptimeMonitor\Models\Monitor as SpatieMonitor;

class Monitor extends SpatieMonitor
{
    protected $casts = [
        'url' => 'string',
        'uptime_last_check_date' => 'datetime',
        'certificate_expiration_date' => 'datetime',
        'uptime_status_last_change_date' => 'datetime',
    ];

    protected $fillable = [
        'url',
        'uptime_check_enabled',
        'certificate_check_enabled',
    ];

    public function getUrlStringAttribute()
    {
        return $this->attributes['url'] ?? '';
    }
}
