<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunCronlessSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:run-cronless-safe
                            {--frequency=60 : Frequency in seconds}
                            {--max-memory=512 : Maximum memory in MB before restart}
                            {--max-runtime=86400 : Maximum runtime in seconds (24 hours)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run cronless scheduler with error handling and monitoring';

    private int $startTime;

    private int $maxMemory;

    private int $maxRuntime;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->startTime = time();
        $this->maxMemory = (int) $this->option('max-memory') * 1024 * 1024; // Convert to bytes
        $this->maxRuntime = (int) $this->option('max-runtime');

        $this->info('Starting cronless scheduler with safety features...');
        $this->info("Max Memory: {$this->option('max-memory')}MB");
        $this->info("Max Runtime: {$this->maxRuntime} seconds");

        // Register shutdown handler
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
                Log::error('Cronless scheduler shutdown due to fatal error', [
                    'error' => $error,
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true),
                    'runtime' => time() - $this->startTime,
                ]);
            }
        });

        // Set error handler
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                Log::warning('Cronless scheduler error', [
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                    'severity' => $severity,
                ]);
            }
        });

        try {
            // Run scheduler with periodic checks
            $this->runSchedulerLoop();
        } catch (\Throwable $e) {
            Log::error('Cronless scheduler crashed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => memory_get_usage(true),
                'runtime' => time() - $this->startTime,
            ]);

            $this->error('Scheduler crashed: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    private function runSchedulerLoop(): void
    {
        $iteration = 0;

        while (true) {
            $iteration++;

            // Check memory usage
            $memoryUsage = memory_get_usage(true);
            if ($memoryUsage > $this->maxMemory) {
                $memoryMB = round($memoryUsage / 1024 / 1024, 2);
                Log::warning('Cronless scheduler stopping: memory limit reached', [
                    'memory_usage' => $memoryMB.'MB',
                    'max_memory' => $this->option('max-memory').'MB',
                    'iteration' => $iteration,
                ]);

                $this->warn("Memory limit reached ({$memoryMB}MB), exiting for restart...");
                break;
            }

            // Check runtime
            $runtime = time() - $this->startTime;
            if ($runtime > $this->maxRuntime) {
                Log::info('Cronless scheduler stopping: max runtime reached', [
                    'runtime' => $runtime,
                    'max_runtime' => $this->maxRuntime,
                    'iteration' => $iteration,
                ]);

                $this->info('Max runtime reached, exiting for restart...');
                break;
            }

            try {
                // Log every 10 iterations
                if ($iteration % 10 === 0) {
                    $memoryMB = round($memoryUsage / 1024 / 1024, 2);
                    $this->comment("[Iteration {$iteration}] Memory: {$memoryMB}MB, Runtime: {$runtime}s");
                }

                // Run the actual scheduler command
                $exitCode = $this->call('schedule:run');

                if ($exitCode !== 0) {
                    Log::warning('Schedule:run returned non-zero exit code', [
                        'exit_code' => $exitCode,
                        'iteration' => $iteration,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Error in scheduler iteration', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'iteration' => $iteration,
                ]);

                $this->error("Iteration {$iteration} failed: ".$e->getMessage());

                // Continue running despite errors
            }

            // Sleep for frequency seconds
            sleep((int) $this->option('frequency'));
        }
    }
}
