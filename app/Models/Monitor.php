<?php

namespace App\Models;

use Spatie\Url\Url;
use Illuminate\Database\Eloquent\Model;
use Spatie\UptimeMonitor\Models\Monitor as SpatieMonitor;

class Monitor extends SpatieMonitor
{
    protected $casts = [
        'uptime_check_enabled' => 'boolean',
        'certificate_check_enabled' => 'boolean',
        'uptime_last_check_date' => 'datetime',
        'uptime_status_last_change_date' => 'datetime',
        'uptime_check_failed_event_fired_on_date' => 'datetime',
        'certificate_expiration_date' => 'datetime',
    ];

    protected $guarded = [];

    protected $appends = ['raw_url'];

    public function scopeEnabled($query)
    {
        return $query
            ->where('uptime_check_enabled', true)
            ->orWhere('certificate_check_enabled', true);
    }

    public function getUrlAttribute(): ?Url
    {
        if (! isset($this->attributes['url'])) {
            return null;
        }

        return Url::fromString($this->attributes['url']);
    }

    public function getRawUrlAttribute(): string
    {
        return (string) $this->url;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_monitor')->withPivot('is_active');
    }

    public function scopeActive($query)
    {
        return $query->whereHas('users', function ($query) {
            $query->where('user_monitor.is_active', true);
        });
    }

    public function scopeDisabled($query)
    {
        return $query->whereHas('users', function ($query) {
            $query->where('user_monitor.is_active', false);
        });
    }

    // boot
    protected static function boot()
    {
        parent::boot();

        // global scope based on logged in user
        static::addGlobalScope('user', function ($query) {
            if (auth()->check()) {
                $query->whereHas('users', function ($query) {
                    $query->where('user_monitor.user_id', auth()->id());
                });
            }
        });

        static::created(function ($monitor) {
            $monitor->users()->attach(auth()->id() ?? 1, ['is_active' => true]);
        });

        static::deleting(function ($monitor) {
            $monitor->users()->detach();
        });
    }
}
