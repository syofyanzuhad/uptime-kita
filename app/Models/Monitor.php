<?php

namespace App\Models;

use Spatie\Url\Url;
use Spatie\Tags\HasTags;
use Spatie\UptimeMonitor\Models\Monitor as SpatieMonitor;

class Monitor extends SpatieMonitor
{
    use HasTags;

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
            ->where('uptime_check_enabled', true);
    }

    public function getUrlAttribute(): ?Url
    {
        if (! isset($this->attributes['url'])) {
            return null;
        }

        return Url::fromString($this->attributes['url']);
    }

    public function getFaviconAttribute(): ?string
    {
        return $this->url ? "https://s2.googleusercontent.com/s2/favicons?domain={$this->url->getHost()}&sz=32" : null;
    }

    public function getRawUrlAttribute(): string
    {
        return (string) $this->url;
    }

    public function getIsSubscribedAttribute(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // return cache()->remember("is_subscribed_{$this->id}_" . auth()->id(), 60, function () {
        // Gunakan koleksi jika relasi sudah dimuat (eager loaded)
        if ($this->relationLoaded('users')) {
            return $this->users->contains('id', auth()->id());
        }

        // Fallback query jika relasi belum dimuat
        return $this->users()->where('user_id', auth()->id())->exists();
        // });
    }

    // Getter for uptime_last_check_date to return 00 seconds in carbon object
    public function getUptimeLastCheckDateAttribute()
    {
        if (!$this->attributes['uptime_last_check_date']) {
            return null;
        }

        $date = Carbon::parse($this->attributes['uptime_last_check_date']);
        return $date->setSeconds(0);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_monitor')->withPivot('is_active');
    }

    public function statusPages()
    {
        return $this->belongsToMany(StatusPage::class, 'status_page_monitor');
    }

    public function histories()
    {
        return $this->hasMany(MonitorHistory::class);
    }

    public function latestHistory()
    {
        return $this->hasOne(MonitorHistory::class)->latest();
    }

    public function uptimes()
    {
        return $this->hasMany(MonitorUptime::class);
    }

    public function uptimesDaily()
    {
        return $this->hasMany(MonitorUptimeDaily::class)
            ->where('date', '>=', now()->subYear()->toDateString())
            ->orderBy('date', 'asc');
    }

    public function uptimeDaily()
    {
        return $this->hasOne(MonitorUptimeDaily::class)->whereDate('date', now()->toDateString());
    }

    public function getTodayUptimePercentageAttribute()
    {
        return $this->uptimeDaily?->uptime_percentage ?? 0;
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

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeSearch($query, $search)
    {
        if (! $search || mb_strlen($search) < 3) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('url', 'like', "%$search%")
                ->orWhere('name', 'like', "%$search%")
                ->orWhereRaw('REPLACE(REPLACE(url, "https://", ""), "http://", "") LIKE ?', ["%$search%"]);
        });
    }

    /**
     * Get the owner of this monitor (the first user who was attached to it)
     */
    public function getOwnerAttribute()
    {
        return $this->users()->orderBy('user_monitor.created_at', 'asc')->first();
    }

    /**
     * Check if the given user is the owner of this monitor
     */
    public function isOwnedBy(User $user): bool
    {
        $owner = $this->owner;
        return $owner && $owner->id === $user->id;
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
        static::addGlobalScope('enabled', function ($query) {
            $query->where('uptime_check_enabled', true);
        });

        static::created(function ($monitor) {
            // attach the current user as the owner of the private monitor
            $monitor->users()->attach(auth()->id() ?? 1, ['is_active' => true]);

            // remove cache
            cache()->forget("private_monitors_page_" . auth()->id() . '_1');
            cache()->forget("public_monitors_authenticated_" . auth()->id() . '_1');
        });

        static::updating(function ($monitor) {
            // history log
            if ($monitor->isDirty('uptime_last_check_date') || $monitor->isDirty('uptime_status')) {
                $monitor->histories()->create([
                    'uptime_status' => $monitor->uptime_status,
                    'message' => $monitor->uptime_check_failure_reason,
                ]);
            }
        });

        static::deleting(function ($monitor) {
            $monitor->users()->detach();
        });
    }
}
