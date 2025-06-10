<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserMonitor extends Pivot
{
    protected $table = 'user_monitor';

    protected $fillable = [
        'user_id',
        'monitor_id',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
