<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RestoreDatabaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    /**
     * Tables that contain essential data and should be backed up.
     * These are configuration and user data that cannot be regenerated.
     */
    private const ESSENTIAL_TABLES = [
        'users',
        'monitors',
        'notification_channels',
        'status_pages',
        'status_page_monitor',
        'user_monitor',
        'tags',
        'taggables',
        'social_accounts',
        'monitor_incidents', // Historical incident data is valuable
    ];

    /**
     * Tables that will be excluded from backup.
     * These contain regenerable data (monitoring history, cache, sessions, etc.)
     */
    private const EXCLUDED_TABLES = [
        'monitor_histories',           // Can be regenerated from monitoring
        'monitor_statistics',          // Aggregated from histories
        'monitor_uptime_dailies',      // Aggregated from histories
        'monitor_performance_hourly',  // Aggregated from histories
        'health_check_result_history_items', // System health logs
        'cache',                       // Temporary cache data
        'cache_locks',                 // Temporary cache locks
        'sessions',                    // User sessions
        'password_reset_tokens',       // Temporary tokens
    ];

    public function index(): Response
    {
        $databasePath = config('database.connections.sqlite.database');
        $isFileBased = $databasePath !== ':memory:' && ! str_starts_with($databasePath, ':memory:');
        $databaseExists = $isFileBased && file_exists($databasePath);
        $databaseSize = $databaseExists ? filesize($databasePath) : 0;

        // Calculate essential data size estimate
        $essentialRecordCount = 0;
        if ($databaseExists) {
            foreach (self::ESSENTIAL_TABLES as $table) {
                try {
                    $essentialRecordCount += DB::table($table)->count();
                } catch (\Exception) {
                    // Table might not exist
                }
            }
        }

        return Inertia::render('settings/Database', [
            'databaseSize' => $databaseSize,
            'databaseExists' => $databaseExists,
            'isFileBased' => $isFileBased,
            'essentialRecordCount' => $essentialRecordCount,
            'essentialTables' => self::ESSENTIAL_TABLES,
            'excludedTables' => self::EXCLUDED_TABLES,
        ]);
    }

    public function download(): StreamedResponse
    {
        $filename = 'uptime-kita-backup-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(function () {
            $this->generateSqlBackup();
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function generateSqlBackup(): void
    {
        // Output header
        echo "-- Uptime Kita Database Backup\n";
        echo '-- Generated: '.now()->toIso8601String()."\n";
        echo "-- Essential data only (excludes monitoring history and cache)\n";
        echo "--\n";
        echo "-- To restore: sqlite3 database.sqlite < backup.sql\n";
        echo "--\n\n";

        echo "PRAGMA foreign_keys = OFF;\n\n";

        // Export migrations table first (important for schema version)
        $this->exportTable('migrations');

        // Export essential tables
        foreach (self::ESSENTIAL_TABLES as $table) {
            $this->exportTable($table);
        }

        echo "PRAGMA foreign_keys = ON;\n";
    }

    private function exportTable(string $table): void
    {
        try {
            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                echo "-- Table '{$table}' is empty\n\n";

                return;
            }

            echo "-- Table: {$table} ({$rows->count()} rows)\n";
            echo "DELETE FROM \"{$table}\";\n";

            foreach ($rows as $row) {
                $columns = array_keys((array) $row);
                $values = array_map(function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }
                    if (is_int($value) || is_float($value)) {
                        return $value;
                    }

                    return "'".str_replace("'", "''", (string) $value)."'";
                }, array_values((array) $row));

                $columnList = '"'.implode('", "', $columns).'"';
                $valueList = implode(', ', $values);

                echo "INSERT INTO \"{$table}\" ({$columnList}) VALUES ({$valueList});\n";
            }

            echo "\n";
        } catch (\Exception $e) {
            echo "-- Error exporting table '{$table}': {$e->getMessage()}\n\n";
        }
    }

    public function restore(RestoreDatabaseRequest $request): RedirectResponse
    {
        $uploadedFile = $request->file('database');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $databasePath = config('database.connections.sqlite.database');

        // Create a backup of the current database before restoring
        $backupPath = $databasePath.'.backup-'.now()->format('Y-m-d-His');
        if (file_exists($databasePath)) {
            copy($databasePath, $backupPath);
        }

        try {
            if ($extension === 'sql') {
                $this->restoreFromSql($uploadedFile->getRealPath(), $databasePath);
            } else {
                $this->restoreFromSqlite($uploadedFile, $databasePath);
            }

            // Clean up the backup file on success
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }

            return back()->with('success', 'Database restored successfully. Please log in again.');
        } catch (\Exception $e) {
            // Restore the backup on failure
            if (file_exists($backupPath)) {
                copy($backupPath, $databasePath);
                unlink($backupPath);
            }

            DB::reconnect('sqlite');

            return back()->withErrors([
                'database' => 'Failed to restore database: '.$e->getMessage(),
            ]);
        }
    }

    private function restoreFromSql(string $sqlFilePath, string $databasePath): void
    {
        // Read and execute the SQL file
        $sql = file_get_contents($sqlFilePath);

        if ($sql === false) {
            throw new \RuntimeException('Failed to read SQL file');
        }

        // Disable foreign keys temporarily
        DB::statement('PRAGMA foreign_keys = OFF');

        // Split SQL into statements and execute each
        $statements = $this->parseSqlStatements($sql);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (! empty($statement) && ! str_starts_with($statement, '--')) {
                DB::unprepared($statement);
            }
        }

        // Re-enable foreign keys
        DB::statement('PRAGMA foreign_keys = ON');

        // Reconnect to ensure changes are applied
        DB::reconnect('sqlite');
        DB::connection('sqlite')->getPdo();
    }

    private function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $currentStatement = '';
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Skip empty lines and comments
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '--')) {
                continue;
            }

            $currentStatement .= $line."\n";

            // Check if statement ends with semicolon
            if (str_ends_with($trimmedLine, ';')) {
                $statements[] = $currentStatement;
                $currentStatement = '';
            }
        }

        // Add any remaining statement
        if (! empty(trim($currentStatement))) {
            $statements[] = $currentStatement;
        }

        return $statements;
    }

    private function restoreFromSqlite(\Illuminate\Http\UploadedFile $uploadedFile, string $databasePath): void
    {
        // Close database connections before replacing
        DB::disconnect('sqlite');

        // Move the uploaded file to replace the database
        $uploadedFile->move(dirname($databasePath), basename($databasePath));

        // Reconnect and verify the database is valid
        DB::reconnect('sqlite');
        DB::connection('sqlite')->getPdo();
    }
}
