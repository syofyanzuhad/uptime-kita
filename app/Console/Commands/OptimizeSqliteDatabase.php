<?php

namespace App\Console\Commands;

use App\Models\MonitorLog;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizeSqliteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sqlite:optimize {--prune-days=90 : Delete logs older than N days}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize SQLite database: prune old logs, vacuum, analyze, and tune pragmas.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dbPath = database_path('database.sqlite');
        $this->info("🧩 Starting optimization for: {$dbPath}");

        try {
            // 1️⃣ Delete old logs (if table exists)
            $days = (int) $this->option('prune-days');
            if (DB::table('sqlite_master')->where('name', 'monitor_logs')->exists()) {
                $count = MonitorLog::where('created_at', '<', now()->subDays($days))->count();
                if ($count > 0) {
                    $this->info("🧹 Deleting {$count} old monitor_logs entries (>{ $days } days)...");
                    MonitorLog::where('created_at', '<', now()->subDays($days))->delete();
                } else {
                    $this->info('✅ No old monitor_logs to delete.');
                }
            }

            // 2️⃣ Optimize PRAGMA settings
            $this->info('⚙️ Applying SQLite PRAGMA tuning...');
            DB::statement('PRAGMA journal_mode = WAL;');
            DB::statement('PRAGMA synchronous = NORMAL;');
            DB::statement('PRAGMA temp_store = MEMORY;');
            DB::statement('PRAGMA cache_size = -20000;');

            // 3️⃣ Analyze query planner
            $this->info('🔍 Running ANALYZE...');
            DB::statement('ANALYZE;');

            // 4️⃣ Compact database
            $this->info('💾 Running VACUUM (this may take a while)...');
            DB::statement('VACUUM;');

            // 5️⃣ Log and notify
            $msg = "✅ SQLite optimization completed successfully for {$dbPath}";
            $this->info($msg);
            Log::info($msg);
            if (app()->bound('sentry')) {
                app('sentry')->captureMessage($msg);
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Optimization failed: '.$e->getMessage());
            Log::error('SQLite optimization failed', ['error' => $e]);
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }

            return Command::FAILURE;
        }
    }
}
