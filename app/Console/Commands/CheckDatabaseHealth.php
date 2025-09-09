<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckDatabaseHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:health-check {--repair : Attempt to repair issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check SQLite database health and optionally repair issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking database health...');
        
        try {
            // Run integrity check
            $integrityCheck = DB::select('PRAGMA integrity_check');
            
            if ($integrityCheck[0]->integrity_check === 'ok') {
                $this->info('✓ Database integrity check passed');
                
                // Check journal mode
                $journalMode = DB::select('PRAGMA journal_mode');
                if (!empty($journalMode)) {
                    $mode = $journalMode[0]->journal_mode ?? 'unknown';
                    $this->info("Journal mode: {$mode}");
                    if ($mode !== 'wal') {
                        $this->warn("⚠ Consider using WAL mode for better concurrency (current: {$mode})");
                    }
                }
                
                // Get database stats
                $pageCount = DB::select('PRAGMA page_count');
                $pageSize = DB::select('PRAGMA page_size');
                if (!empty($pageCount) && !empty($pageSize)) {
                    $pages = $pageCount[0]->page_count ?? 0;
                    $size = $pageSize[0]->page_size ?? 0;
                    $dbSize = ($pages * $size) / (1024 * 1024);
                    $this->info(sprintf("Database size: %.2f MB", $dbSize));
                }
                
                // Check for locked tables
                $busyTimeout = DB::select('PRAGMA busy_timeout');
                if (!empty($busyTimeout)) {
                    $timeout = $busyTimeout[0]->timeout ?? $busyTimeout[0]->busy_timeout ?? 'unknown';
                    $this->info("Busy timeout: {$timeout}ms");
                }
                
                if ($this->option('repair')) {
                    $this->performOptimizations();
                }
                
                return Command::SUCCESS;
            } else {
                $this->error('✗ Database integrity check failed!');
                foreach ($integrityCheck as $error) {
                    $this->error($error->integrity_check);
                }
                
                Log::critical('Database integrity check failed', [
                    'errors' => $integrityCheck
                ]);
                
                if ($this->option('repair')) {
                    $this->warn('Attempting repair...');
                    $this->attemptRepair();
                }
                
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Failed to check database health: ' . $e->getMessage());
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
    
    private function performOptimizations()
    {
        $this->info('Performing optimizations...');
        
        try {
            // Vacuum database
            DB::statement('VACUUM');
            $this->info('✓ Database vacuumed');
            
            // Analyze tables
            DB::statement('ANALYZE');
            $this->info('✓ Database analyzed');
            
            // Set optimal pragmas
            DB::statement('PRAGMA journal_mode = WAL');
            DB::statement('PRAGMA synchronous = NORMAL');
            DB::statement('PRAGMA busy_timeout = 5000');
            DB::statement('PRAGMA cache_size = -64000');
            $this->info('✓ Optimizations applied');
            
        } catch (\Exception $e) {
            $this->error('Optimization failed: ' . $e->getMessage());
        }
    }
    
    private function attemptRepair()
    {
        $dbPath = database_path('database.sqlite');
        $backupPath = database_path('database_backup_' . date('Y-m-d_H-i-s') . '.sqlite');
        
        $this->info("Creating backup at: {$backupPath}");
        copy($dbPath, $backupPath);
        
        $this->info('Attempting recovery...');
        $recoveryPath = database_path('database_recovered.sqlite');
        
        // Try to recover using sqlite3 command
        $command = "sqlite3 {$dbPath} '.recover' | sqlite3 {$recoveryPath}";
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($recoveryPath)) {
            $this->info('✓ Recovery successful! New database created at: ' . $recoveryPath);
            $this->warn('To use the recovered database:');
            $this->warn("1. php artisan down");
            $this->warn("2. mv {$dbPath} {$dbPath}.corrupted");
            $this->warn("3. mv {$recoveryPath} {$dbPath}");
            $this->warn("4. php artisan up");
        } else {
            $this->error('Recovery failed. Please restore from backup.');
        }
    }
}
