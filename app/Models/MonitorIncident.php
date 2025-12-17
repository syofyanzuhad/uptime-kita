<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitor_id',
        'type',
        'started_at',
        'ended_at',
        'duration_minutes',
        'reason',
        'response_time',
        'status_code',
        'down_alert_sent',
        'last_alert_at_failure_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'response_time' => 'integer',
        'status_code' => 'integer',
        'duration_minutes' => 'integer',
        'down_alert_sent' => 'boolean',
        'last_alert_at_failure_count' => 'integer',
    ];

    /**
     * Get the monitor that owns the incident.
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Scope a query to only include recent incidents.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days))
            ->orderBy('started_at', 'desc');
    }

    /**
     * Scope a query to only include ongoing incidents.
     */
    public function scopeOngoing($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Calculate and set the duration when ending an incident.
     */
    public function endIncident(): void
    {
        $this->ended_at = now();
        $this->duration_minutes = $this->started_at->diffInMinutes($this->ended_at);
        $this->save();
    }

    /**
     * Check if any DOWN alert was sent during this incident.
     */
    public function wasAlertSent(): bool
    {
        return $this->down_alert_sent;
    }
}
