<?php

namespace App\Models;

use App\Services\MonitorHistoryDatabaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class MonitorHistory extends Model
{
    use Prunable;

    protected $fillable = [
        'id',
        'monitor_id',
        'uptime_status',
        'message',
        'response_data',
        'response_time_ms',
        'certificate_status',
        'certificate_expiration_date',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'response_data' => 'array',
        'certificate_expiration_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the monitor that owns this history record
     */
    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Get the latest history record for a specific monitor
     * Now uses the dynamic SQLite database system
     */
    public static function scopeLatestByMonitorId($query, $monitorId)
    {
        // Use the new dynamic database system
        $service = new MonitorHistoryDatabaseService;
        $latestRecord = $service->getLatestHistory($monitorId);

        if ($latestRecord) {
            // Create a model instance from the array data
            $model = new static;
            $latestRecord['monitor_id'] = $monitorId; // Add monitor_id
            $model->fill($latestRecord);
            $model->exists = true;

            return $model;
        }

        return null;
    }

    /**
     * Get the latest history records for multiple monitors
     * Now uses the dynamic SQLite database system
     */
    public static function scopeLatestByMonitorIds($query, $monitorIds)
    {
        $service = new MonitorHistoryDatabaseService;
        $results = collect();

        foreach ($monitorIds as $monitorId) {
            $latestRecord = $service->getLatestHistory($monitorId);
            if ($latestRecord) {
                $model = new static;
                $model->fill($latestRecord);
                $model->exists = true;
                $results->push($model);
            }
        }

        return $results;
    }

    /**
     * Get history records for a specific monitor
     * Now uses the dynamic SQLite database system
     */
    public static function getForMonitor(int $monitorId, int $limit = 100, int $offset = 0): array
    {
        if (! $monitorId) {
            return [];
        }
        $service = new MonitorHistoryDatabaseService;

        return $service->getHistory($monitorId, $limit, $offset);
    }

    /**
     * Create a history record for a specific monitor
     * Now uses the dynamic SQLite database system
     */
    public static function createForMonitor(int $monitorId, array $data): bool
    {
        if (! $monitorId) {
            return false;
        }
        $service = new MonitorHistoryDatabaseService;

        return $service->insertHistory($monitorId, $data);
    }

    /**
     * Clean up old history records for a specific monitor
     * Now uses the dynamic SQLite database system
     */
    public static function cleanupForMonitor(int $monitorId, int $daysToKeep = 30): int
    {
        if (! $monitorId) {
            return 0;
        }
        $service = new MonitorHistoryDatabaseService;

        return $service->cleanupOldHistory($monitorId, $daysToKeep);
    }

    /**
     * Check if a monitor has a database
     */
    public static function monitorHasDatabase(int $monitorId): bool
    {
        if (! $monitorId) {
            return false;
        }
        $service = new MonitorHistoryDatabaseService;

        return $service->monitorDatabaseExists($monitorId);
    }

    /**
     * Ensure database exists for a monitor
     */
    public static function ensureMonitorDatabase(int $monitorId): bool
    {
        if (! $monitorId) {
            return false;
        }
        $service = new MonitorHistoryDatabaseService;

        return $service->createMonitorDatabase($monitorId);
    }

    /**
     * Prunable implementation for the main database
     * Note: This is kept for backward compatibility but may not be used
     * since we're now using individual SQLite databases
     */
    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }

    /**
     * Override the create method to use the dynamic database system
     */
    public static function create(array $attributes = [])
    {
        if (isset($attributes['monitor_id'])) {
            return static::createForMonitor($attributes['monitor_id'], $attributes);
        }

        return parent::create($attributes);
    }

    /**
     * Get the database connection for this model
     * This ensures we use the correct connection for the monitor
     */
    public function getConnectionName()
    {
        if ($this->monitor_id) {
            $service = new MonitorHistoryDatabaseService;
            $service->setMonitorDatabaseConnection($this->monitor_id);

            return 'sqlite_monitor_history';
        }

        return parent::getConnectionName();
    }
}
