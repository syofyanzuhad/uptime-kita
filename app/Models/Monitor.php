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

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_monitor')->withPivot('is_active');
    }

    public function statusPages()
    {
        return $this->belongsToMany(StatusPage::class, 'status_page_monitor');
    }

    /**
     * Get all history records for this monitor from the dynamic SQLite database
     */
    public function histories(int $limit = 100, int $offset = 0): array
    {
        if (!$this->id) {
            return [];
        }
        return MonitorHistory::getForMonitor($this->id, $limit, $offset);
    }

    /**
     * Get the latest history record for this monitor from the dynamic SQLite database
     */
    public function latestHistory(): ?MonitorHistory
    {
        if (!$this->id) {
            return null;
        }
        return MonitorHistory::scopeLatestByMonitorId(null, $this->id);
    }

    /**
     * Get history records with pagination
     */
    public function getHistoryPaginated(int $page = 1, int $perPage = 100): array
    {
        if (!$this->id) {
            return [];
        }
        $offset = ($page - 1) * $perPage;
        return MonitorHistory::getForMonitor($this->id, $perPage, $offset);
    }

    /**
     * Get history statistics for this monitor
     */
    public function getHistoryStatistics(): array
    {
        if (!$this->id) {
            return [
                'total_records' => 0,
                'status_counts' => [
                    'up' => 0,
                    'down' => 0,
                    'not yet checked' => 0,
                ],
                'uptime_percentage' => 0,
                'average_response_time' => 0,
                'last_check' => null,
            ];
        }
        $records = MonitorHistory::getForMonitor($this->id, 10000, 0);

        $totalRecords = count($records);
        $statusCounts = [
            'up' => 0,
            'down' => 0,
            'not yet checked' => 0,
        ];

        $responseTimes = [];
        $lastCheck = null;

        foreach ($records as $record) {
            $status = $record['uptime_status'];
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }

            if (isset($record['response_time_ms']) && $record['response_time_ms']) {
                $responseTimes[] = $record['response_time_ms'];
            }

            if (!$lastCheck || $record['created_at'] > $lastCheck) {
                $lastCheck = $record['created_at'];
            }
        }

        // Calculate uptime percentage
        $uptimePercentage = $totalRecords > 0
            ? round(($statusCounts['up'] / $totalRecords) * 100, 2)
            : 0;

        // Calculate average response time
        $averageResponseTime = count($responseTimes) > 0
            ? round(array_sum($responseTimes) / count($responseTimes), 2)
            : 0;

        return [
            'total_records' => $totalRecords,
            'status_counts' => $statusCounts,
            'uptime_percentage' => $uptimePercentage,
            'average_response_time' => $averageResponseTime,
            'last_check' => $lastCheck,
        ];
    }

    /**
     * Check if this monitor has a history database
     */
    public function hasHistoryDatabase(): bool
    {
        if (!$this->id) {
            return false;
        }
        return MonitorHistory::monitorHasDatabase($this->id);
    }

    /**
     * Ensure history database exists for this monitor
     */
    public function ensureHistoryDatabase(): bool
    {
        if (!$this->id) {
            return false;
        }
        return MonitorHistory::ensureMonitorDatabase($this->id);
    }

    /**
     * Clean up old history records for this monitor
     */
    public function cleanupHistory(int $daysToKeep = 30): int
    {
        if (!$this->id) {
            return 0;
        }
        return MonitorHistory::cleanupForMonitor($this->id, $daysToKeep);
    }

        /**
     * Create a history record for this monitor
     */
    public function createHistoryRecord(array $data = []): bool
    {
        if (!$this->id) {
            return false;
        }

        $defaultData = [
            'uptime_status' => $this->uptime_status,
            'message' => $this->uptime_check_failure_reason,
            'certificate_status' => $this->certificate_status,
            'certificate_expiration_date' => $this->certificate_expiration_date,
        ];

        $historyData = array_merge($defaultData, $data);

        return MonitorHistory::createForMonitor($this->id, $historyData);
    }

    /**
     * Get the total number of history records for this monitor
     */
    public function getHistoryCount(): int
    {
        if (!$this->id) {
            return 0;
        }
        $records = MonitorHistory::getForMonitor($this->id, 1, 0);
        return count($records);
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

            // Create SQLite database for this monitor's history
            \App\Models\MonitorHistory::ensureMonitorDatabase($monitor->id);

            // remove cache
            cache()->forget("private_monitors_page_" . auth()->id() . '_1');
            cache()->forget("public_monitors_authenticated_" . auth()->id() . '_1');
        });

        static::updating(function ($monitor) {
            // history log
            if ($monitor->isDirty('uptime_last_check_date') || $monitor->isDirty('uptime_status')) {
                // Create history record in the monitor's dedicated SQLite database
                $monitor->createHistoryRecord();
            }
        });

        static::deleting(function ($monitor) {
            $monitor->users()->detach();

            // Delete the monitor's SQLite database
            $service = new \App\Services\MonitorHistoryDatabaseService();
            $service->deleteMonitorDatabase($monitor->id);
        });
    }
}
