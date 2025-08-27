<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserMonitor extends Pivot
{
    use HasFactory;

    protected $table = 'user_monitor';

    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'monitor_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
