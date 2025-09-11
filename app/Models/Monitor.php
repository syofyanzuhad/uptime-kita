<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Spatie\Tags\HasTags;
use Spatie\UptimeMonitor\Models\Monitor as SpatieMonitor;
use Spatie\Url\Url;

class Monitor extends SpatieMonitor
{
    use HasFactory, HasTags;

    protected $casts = [
        'uptime_check_enabled' => 'boolean',
        'certificate_check_enabled' => 'boolean',
        'uptime_last_check_date' => 'datetime',
        'uptime_status_last_change_date' => 'datetime',
        'uptime_check_failed_event_fired_on_date' => 'datetime',
        'certificate_expiration_date' => 'datetime',
        'expected_status_code' => 'integer',
        'max_response_time' => 'integer',
        'check_locations' => 'array',
        'notification_settings' => 'array',
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
        // Check directly in pivot table to avoid issues with global scopes
        return \DB::table('user_monitor')
            ->where('monitor_id', $this->id)
            ->where('user_id', auth()->id())
            ->exists();
        // });
    }

    public function getIsPinnedAttribute(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // Use cache for pinned status
        return cache()->remember("is_pinned_{$this->id}_".auth()->id(), 300, function () {
            // Use collection if relation is already loaded (eager loaded)
            if ($this->relationLoaded('users')) {
                $userPivot = $this->users->firstWhere('id', auth()->id())?->pivot;

                return $userPivot ? (bool) $userPivot->is_pinned : false;
            }

            // Fallback query if relation is not loaded
            // Check directly in pivot table to avoid issues with global scopes
            $pivot = \DB::table('user_monitor')
                ->where('monitor_id', $this->id)
                ->where('user_id', auth()->id())
                ->first();

            return $pivot ? (bool) $pivot->is_pinned : false;
        });
    }

    // Getter for uptime_last_check_date to return 00 seconds in carbon object
    public function getUptimeLastCheckDateAttribute()
    {
        if (! $this->attributes['uptime_last_check_date']) {
            return null;
        }

        $date = Carbon::parse($this->attributes['uptime_last_check_date']);

        return $date->setSeconds(0);
    }

    public function getHostAttribute()
    {
        return $this->url->getHost();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_monitor')->withPivot('is_active', 'is_pinned');
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

    public function statistics()
    {
        return $this->hasOne(MonitorStatistic::class);
    }

    /**
     * Get the incidents for the monitor.
     */
    public function incidents()
    {
        return $this->hasMany(MonitorIncident::class);
    }

    /**
     * Get recent incidents for the monitor.
     */
    public function recentIncidents()
    {
        return $this->hasMany(MonitorIncident::class)
            ->recent(30)
            ->limit(10);
    }

    /**
     * Get the performance records for the monitor.
     */
    public function performanceHourly()
    {
        return $this->hasMany(MonitorPerformanceHourly::class);
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

    public function scopePinned($query)
    {
        return $query->whereHas('users', function ($query) {
            $query->where('user_monitor.user_id', auth()->id())
                ->where('user_monitor.is_pinned', true);
        });
    }

    public function scopeNotPinned($query)
    {
        return $query->whereHas('users', function ($query) {
            $query->where('user_monitor.user_id', auth()->id())
                ->where('user_monitor.is_pinned', false);
        });
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

    /**
     * Create or update history record for the current minute
     * Ensures only one history record per monitor per minute
     */
    public function createOrUpdateHistory(array $data): MonitorHistory
    {
        $now = now();
        $minuteStart = $now->copy()->setSeconds(0)->setMicroseconds(0);

        // Use updateOrCreate to ensure only one record per minute
        return $this->histories()->updateOrCreate(
            [
                'monitor_id' => $this->id,
                // Use a minute-rounded timestamp for uniqueness
                'created_at' => $minuteStart,
            ],
            [
                'uptime_status' => $data['uptime_status'],
                'message' => $data['message'] ?? $this->uptime_check_failure_reason,
                'response_time' => $data['response_time'] ?? null,
                'status_code' => $data['status_code'] ?? null,
                'checked_at' => $data['checked_at'] ?? $now,
                'updated_at' => $now,
            ]
        );
    }

    // boot
    protected static function boot()
    {
        parent::boot();

        // global scope based on logged in user
        static::addGlobalScope('user', function ($query) {
            if (auth()->check() && ! auth()->user()?->is_admin) {
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
            // Only attach if there's an authenticated user
            if (auth()->id()) {
                $monitor->users()->attach(auth()->id(), [
                    'is_active' => true,
                    'is_pinned' => false,
                ]);
            }

            // remove cache
            cache()->forget('private_monitors_page_'.auth()->id().'_1');
            cache()->forget('public_monitors_authenticated_'.auth()->id().'_1');
        });

        static::updating(function ($monitor) {
            // history log
            if ($monitor->isDirty('uptime_last_check_date') || $monitor->isDirty('uptime_status')) {
                $monitor->createOrUpdateHistory([
                    'uptime_status' => $monitor->uptime_status,
                    'message' => $monitor->uptime_check_failure_reason,
                    'response_time' => null, // Response time should be passed when available, not retrieved from model
                    'status_code' => null, // Status code should be passed when available, not retrieved from model
                    'checked_at' => $monitor->uptime_last_check_date,
                ]);
            }
        });

        static::deleting(function ($monitor) {
            $monitor->users()->detach();
        });
    }
}
