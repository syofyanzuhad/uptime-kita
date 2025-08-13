<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;

class MonitorHistoryDatabaseService
{
    /**
     * Create a new SQLite database for a specific monitor
     */
    public function createMonitorDatabase(int $monitorId): bool
    {
        $databasePath = $this->getMonitorDatabasePath($monitorId);
        $databaseDir = dirname($databasePath);

        // Create directory if it doesn't exist
        if (!File::exists($databaseDir)) {
            File::makeDirectory($databaseDir, 0755, true);
        }

        // Create the SQLite database file
        if (!File::exists($databasePath)) {
            File::put($databasePath, '');
        }

        // Set up the database connection
        $this->setMonitorDatabaseConnection($monitorId);

        // Create the monitor_histories table
        return $this->createMonitorHistoriesTable($monitorId);
    }

    /**
     * Get the database path for a specific monitor
     */
    public function getMonitorDatabasePath(int $monitorId): string
    {
        return database_path("monitor-histories/{$monitorId}.sqlite");
    }

        /**
     * Set up the database connection for a specific monitor
     */
    public function setMonitorDatabaseConnection(int $monitorId): void
    {
        $databasePath = $this->getMonitorDatabasePath($monitorId);
        
        // Set the complete configuration for the connection
        config([
            'database.connections.sqlite_monitor_history' => [
                'driver' => 'sqlite',
                'database' => $databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 10000,
                'journal_mode' => 'WAL',
                'synchronous' => 'NORMAL',
            ]
        ]);

        // Purge the connection to ensure it uses the new configuration
        DB::purge('sqlite_monitor_history');
    }

    /**
     * Create the monitor_histories table in the monitor's database
     */
    public function createMonitorHistoriesTable(int $monitorId): bool
    {
        try {
            $this->setMonitorDatabaseConnection($monitorId);

            // Check if table already exists
            if (Schema::connection('sqlite_monitor_history')->hasTable('monitor_histories')) {
                return true;
            }

            Schema::connection('sqlite_monitor_history')->create('monitor_histories', function (Blueprint $table) {
                $table->id();
                $table->string('uptime_status'); // up, down, not yet checked
                $table->text('message')->nullable();
                $table->json('response_data')->nullable(); // Store additional response data
                $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
                $table->string('certificate_status')->nullable(); // valid, invalid, not applicable
                $table->timestamp('certificate_expiration_date')->nullable();
                $table->timestamps();

                // Indexes for better performance
                $table->index(['uptime_status', 'created_at']);
                $table->index('created_at');
            });

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to create monitor histories table for monitor {$monitorId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a monitor's database exists
     */
    public function monitorDatabaseExists(int $monitorId): bool
    {
        $databasePath = $this->getMonitorDatabasePath($monitorId);
        return File::exists($databasePath);
    }

    /**
     * Delete a monitor's database
     */
    public function deleteMonitorDatabase(int $monitorId): bool
    {
        try {
            $databasePath = $this->getMonitorDatabasePath($monitorId);

            if (File::exists($databasePath)) {
                File::delete($databasePath);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to delete monitor database for monitor {$monitorId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database connection for a specific monitor
     */
    public function getMonitorConnection(int $monitorId)
    {
        if (!$this->monitorDatabaseExists($monitorId)) {
            $this->createMonitorDatabase($monitorId);
        }

        $this->setMonitorDatabaseConnection($monitorId);

        // Purge and reconnect to ensure fresh connection
        DB::purge('sqlite_monitor_history');
        return DB::connection('sqlite_monitor_history');
    }

    /**
     * Insert a history record into the monitor's database
     */
    public function insertHistory(int $monitorId, array $data): bool
    {
        try {
            $connection = $this->getMonitorConnection($monitorId);

            $result = $connection->table('monitor_histories')->insert([
                'uptime_status' => $data['uptime_status'],
                'message' => $data['message'] ?? null,
                'response_data' => isset($data['response_data']) ? json_encode($data['response_data']) : null,
                'response_time_ms' => $data['response_time_ms'] ?? null,
                'certificate_status' => $data['certificate_status'] ?? null,
                'certificate_expiration_date' => $data['certificate_expiration_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $result === true || $result === 1;
        } catch (\Exception $e) {
            \Log::error("Failed to insert history for monitor {$monitorId}: " . $e->getMessage());
            return false;
        }
    }

        /**
     * Get history records from the monitor's database
     */
    public function getHistory(int $monitorId, int $limit = 100, int $offset = 0): array
    {
        try {
            $connection = $this->getMonitorConnection($monitorId);
            
            $records = $connection->table('monitor_histories')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
            
            // Convert to array and ensure all records are arrays
            return $records->map(function ($record) {
                return (array) $record;
            })->toArray();
        } catch (\Exception $e) {
            \Log::error("Failed to get history for monitor {$monitorId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the latest history record from the monitor's database
     */
    public function getLatestHistory(int $monitorId): ?array
    {
        try {
            $connection = $this->getMonitorConnection($monitorId);

            $result = $connection->table('monitor_histories')
                ->orderBy('created_at', 'desc')
                ->first();

            return $result ? (array) $result : null;
        } catch (\Exception $e) {
            \Log::error("Failed to get latest history for monitor {$monitorId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean up old history records (older than specified days)
     */
    public function cleanupOldHistory(int $monitorId, int $daysToKeep = 30): int
    {
        try {
            $connection = $this->getMonitorConnection($monitorId);

            $cutoffDate = now()->subDays($daysToKeep);

            return $connection->table('monitor_histories')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        } catch (\Exception $e) {
            \Log::error("Failed to cleanup old history for monitor {$monitorId}: " . $e->getMessage());
            return 0;
        }
    }
}
