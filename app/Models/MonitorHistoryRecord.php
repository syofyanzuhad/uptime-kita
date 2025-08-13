<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\MonitorHistoryDatabaseService;

class MonitorHistoryRecord extends Model
{
    protected $table = 'monitor_histories';

    protected $connection = 'sqlite_monitor_history';

    protected $fillable = [
        'uptime_status',
        'message',
        'response_data',
        'response_time_ms',
        'certificate_status',
        'certificate_expiration_date',
    ];

    protected $casts = [
        'response_data' => 'array',
        'certificate_expiration_date' => 'datetime',
    ];

    /**
     * Set the monitor ID for this record
     */
    public function setMonitorId(int $monitorId): void
    {
        $this->monitor_id = $monitorId;
    }

    /**
     * Get the monitor ID for this record
     */
    public function getMonitorId(): int
    {
        return $this->monitor_id;
    }

    /**
     * Create a new history record for a specific monitor
     */
    public static function createForMonitor(int $monitorId, array $data): bool
    {
        $service = new MonitorHistoryDatabaseService();
        return $service->insertHistory($monitorId, $data);
    }

    /**
     * Get history records for a specific monitor
     */
    public static function getForMonitor(int $monitorId, int $limit = 100, int $offset = 0): array
    {
        $service = new MonitorHistoryDatabaseService();
        return $service->getHistory($monitorId, $limit, $offset);
    }

    /**
     * Get the latest history record for a specific monitor
     */
    public static function getLatestForMonitor(int $monitorId): ?array
    {
        $service = new MonitorHistoryDatabaseService();
        return $service->getLatestHistory($monitorId);
    }

    /**
     * Clean up old history records for a specific monitor
     */
    public static function cleanupForMonitor(int $monitorId, int $daysToKeep = 30): int
    {
        $service = new MonitorHistoryDatabaseService();
        return $service->cleanupOldHistory($monitorId, $daysToKeep);
    }

    /**
     * Check if a monitor has a database
     */
    public static function monitorHasDatabase(int $monitorId): bool
    {
        $service = new MonitorHistoryDatabaseService();
        return $service->monitorDatabaseExists($monitorId);
    }

    /**
     * Create database for a monitor if it doesn't exist
     */
    public static function ensureMonitorDatabase(int $monitorId): bool
    {
        $service = new MonitorHistoryDatabaseService();
        return $service->createMonitorDatabase($monitorId);
    }
}
