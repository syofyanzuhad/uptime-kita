<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RestoreDatabaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $driver = DB::getDriverName();
        $databaseSize = 0;
        $isFileBased = false;
        $databaseExists = true;

        if ($driver === 'sqlite') {
            $databasePath = config('database.connections.sqlite.database');
            $isFileBased = $databasePath !== ':memory:' && ! str_starts_with((string) $databasePath, ':memory:');
            $databaseExists = $isFileBased && file_exists((string) $databasePath);
            $databaseSize = $databaseExists ? filesize((string) $databasePath) : 0;
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            $databaseName = DB::getDatabaseName();
            try {
                $size = DB::selectOne('
                    SELECT SUM(data_length + index_length) AS size 
                    FROM information_schema.TABLES 
                    WHERE table_schema = ?
                ', [$databaseName]);
                $databaseSize = (int) ($size->size ?? 0);
            } catch (\Throwable) {
                $databaseSize = 0;
            }
        } elseif ($driver === 'pgsql') {
            $databaseName = DB::getDatabaseName();
            try {
                $size = DB::selectOne('SELECT pg_database_size(?) AS size', [$databaseName]);
                $databaseSize = (int) ($size->size ?? 0);
            } catch (\Throwable) {
                $databaseSize = 0;
            }
        }

        // Calculate essential data size estimate
        $essentialRecordCount = 0;
        if ($databaseExists) {
            foreach (self::ESSENTIAL_TABLES as $table) {
                try {
                    $essentialRecordCount += DB::table($table)->count();
                } catch (\Throwable) {
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
        $driver = DB::getDriverName();

        // Output header
        echo "-- Uptime Kita Database Backup\n";
        echo '-- Generated: '.now()->toIso8601String()."\n";
        echo "-- Essential data only (excludes monitoring history and cache)\n";
        echo "--\n";
        if ($driver === 'sqlite') {
            echo "-- To restore: sqlite3 database.sqlite < backup.sql\n";
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            echo "-- To restore: mysql -u user -p database < backup.sql\n";
        }
        echo "--\n\n";

        if ($driver === 'sqlite') {
            echo "PRAGMA foreign_keys = OFF;\n\n";
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        }

        // Export migrations table first (important for schema version)
        $this->exportTable('migrations');

        // Export essential tables
        foreach (self::ESSENTIAL_TABLES as $table) {
            $this->exportTable($table);
        }

        if ($driver === 'sqlite') {
            echo "PRAGMA foreign_keys = ON;\n";
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        }
    }

    private function exportTable(string $table): void
    {
        try {
            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                echo "-- Table '{$table}' is empty\n\n";

                return;
            }

            $grammar = DB::getQueryGrammar();
            $wrappedTable = $grammar->wrapTable($table);

            echo "-- Table: {$table} ({$rows->count()} rows)\n";
            echo "DELETE FROM {$wrappedTable};\n";

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
                        return (string) $value;
                    }

                    try {
                        return DB::getPdo()->quote((string) $value);
                    } catch (\Throwable) {
                        return "'".str_replace("'", "''", (string) $value)."'";
                    }
                }, array_values((array) $row));

                $columnList = implode(', ', array_map([$grammar, 'wrap'], $columns));
                $valueList = implode(', ', $values);

                echo "INSERT INTO {$wrappedTable} ({$columnList}) VALUES ({$valueList});\n";
            }

            echo "\n";
        } catch (\Throwable $e) {
            echo "-- Error exporting table '{$table}': {$e->getMessage()}\n\n";
        }
    }

    public function restore(RestoreDatabaseRequest $request): RedirectResponse
    {
        $uploadedFile = $request->file('database');
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $driver = DB::getDriverName();
        $databasePath = $driver === 'sqlite' ? config('database.connections.sqlite.database') : null;
        $isFileBasedSqlite = $driver === 'sqlite' && $databasePath && $databasePath !== ':memory:' && ! str_starts_with((string) $databasePath, ':memory:');

        if ($extension !== 'sql' && ! $isFileBasedSqlite) {
            return back()->withErrors([
                'database' => 'Binary SQLite database files (.'.$extension.') can only be restored when using a file-based SQLite database. Please upload a .sql file instead.',
            ]);
        }

        // Create a backup of the current database before restoring (for file-based SQLite)
        $backupPath = $isFileBasedSqlite && file_exists((string) $databasePath)
            ? $databasePath.'.backup-'.now()->format('Y-m-d-His')
            : null;

        if ($backupPath) {
            copy((string) $databasePath, $backupPath);
        }

        try {
            if ($extension === 'sql') {
                $this->restoreFromSql($uploadedFile->getRealPath());
            } else {
                $this->restoreFromSqlite($uploadedFile, (string) $databasePath);
            }

            // Clean up the backup file on success
            if ($backupPath && file_exists($backupPath)) {
                unlink($backupPath);
            }

            return back()->with('success', 'Database restored successfully. Please log in again.');
        } catch (\Throwable $e) {
            // Restore the backup on failure
            if ($backupPath && file_exists($backupPath)) {
                copy($backupPath, (string) $databasePath);
                unlink($backupPath);
                DB::reconnect('sqlite');
            }

            return back()->withErrors([
                'database' => 'Failed to restore database: '.$e->getMessage(),
            ]);
        }
    }

    private function restoreFromSql(string $sqlFilePath): void
    {
        @set_time_limit(300);

        // Read and execute the SQL file
        $sql = file_get_contents($sqlFilePath);

        if ($sql === false) {
            throw new \RuntimeException('Failed to read SQL file');
        }

        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Enable ANSI_QUOTES so MySQL/MariaDB treats double quotes as identifier quotes (ANSI SQL compatible)
            DB::statement("SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'ANSI_QUOTES')");
        }

        $statements = $this->parseSqlStatements($sql);

        // Filter and collect valid executable statements
        $executableStatements = [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || str_starts_with($statement, '--')) {
                continue;
            }

            // Skip vendor-specific foreign key statements that might conflict across database engines
            $upper = strtoupper($statement);
            if (str_starts_with($upper, 'PRAGMA FOREIGN_KEYS') || str_starts_with($upper, 'SET FOREIGN_KEY_CHECKS')) {
                continue;
            }

            $executableStatements[] = $statement;
        }

        Schema::withoutForeignKeyConstraints(function () use ($executableStatements) {
            DB::transaction(function () use ($executableStatements) {
                $batch = '';
                $count = 0;

                foreach ($executableStatements as $statement) {
                    $batch .= $statement."\n";
                    $count++;

                    // Send queries in batches of 100 to minimize network roundtrips and avoid gateway timeouts
                    if ($count >= 100) {
                        DB::unprepared($batch);
                        $batch = '';
                        $count = 0;
                    }
                }

                if (! empty(trim($batch))) {
                    DB::unprepared($batch);
                }
            });
        });
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

    private function restoreFromSqlite(UploadedFile $uploadedFile, string $databasePath): void
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
