<?php

use App\Jobs\CalculateMonitorUptimeDailyJob;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\UptimeMonitor\Commands\CheckCertificates;
use Spatie\UptimeMonitor\Commands\CheckUptime;

Schedule::command(CheckUptime::class)->everyMinute()
    ->onSuccess(function () {
        info('UPTIME-CHECK: SUCCESS');
    })
    ->onFailure(function () {
        info('UPTIME-CHECK: FAILED');
    })
    ->thenPing('https://ping.ohdear.app/c95a0d26-167b-4b51-b806-83529754132b');
// ->withoutOverlapping()
// ->runInBackground();
Schedule::command(CheckCertificates::class)->daily();

// === LARAVEL HORIZON ===
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('horizon:forget --all')->daily();
Schedule::command('queue:prune-batches')->daily();

// === LARAVEL TELOSCOPE ===
Schedule::command('telescope:prune --hours=48')->everyOddHour();

// === LARAVEL PRUNABLE MODELS ===
Schedule::command('model:prune')->daily();
Schedule::command('model:prune', ['--model' => [\Spatie\Health\Models\HealthCheckResultHistoryItem::class]])->daily();

Schedule::job(new CalculateMonitorUptimeDailyJob)->everyFifteenMinutes()
    ->thenPing('https://ping.ohdear.app/f23d1683-f210-4ba9-8852-c933d8ca6f99');

// Calculate monitor statistics for public monitors every 15 minutes using a job
Schedule::job(new \App\Jobs\CalculateMonitorStatisticsJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping();
// Schedule::job(new CalculateMonitorUptimeJob('WEEKLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('MONTHLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('YEARLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('ALL'))->hourly();

Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class)->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
Schedule::command(\Spatie\Health\Commands\ScheduleCheckHeartbeatCommand::class)->everyMinute();
Schedule::command(\Spatie\Health\Commands\DispatchQueueCheckJobsCommand::class)->everyMinute();
Schedule::command('sitemap:generate')->daily();

Schedule::command('sqlite:optimize')->weeklyOn(0, '2:00');

// Update maintenance status for monitors every minute
Schedule::command('monitor:update-maintenance-status')->everyMinute();

// Cleanup expired one-time maintenance windows daily
Schedule::command('monitor:update-maintenance-status --cleanup')->daily();

// === BACKUP DB ===
// Schedule::command('backup:clean')->daily()->at('01:00');
// Schedule::command('backup:run')->daily()->at('01:30')
//     ->onFailure(function () {
//         info('BACKUP-DB: SUCCESS');
//     })
//     ->onSuccess(function () {
//         info('BACKUP-DB: FAILED');
//     });

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:telegram-notification', function () {
    $this->info('Testing Telegram notification...');

    // Get the first user with Telegram notification channel enabled
    $user = User::whereHas('notificationChannels', function ($query) {
        $query->where('type', 'telegram')
            ->where('is_enabled', true);
    })->first();

    if (! $user) {
        $this->error('No user found with enabled Telegram notification channel.');
        $this->info('Please ensure you have a user with Telegram notification channel configured.');

        return 1;
    }

    $this->info("Found user: {$user->name} ({$user->email})");

    // Get Telegram channel info
    $telegramChannel = $user->notificationChannels()
        ->where('type', 'telegram')
        ->where('is_enabled', true)
        ->first();

    $this->info("Telegram destination: {$telegramChannel->destination}");

    // Create test data
    $testData = [
        'id' => 1,
        'url' => 'https://example.com',
        'status' => 'DOWN',
    ];

    try {
        // Send test notification
        $user->notify(new MonitorStatusChanged($testData));

        $this->info('✅ Telegram notification sent successfully!');
        $this->info('Check your Telegram chat for the test message.');
    } catch (\Exception $e) {
        $this->error('❌ Failed to send Telegram notification:');
        $this->error($e->getMessage());

        return 1;
    }
})->purpose('Test Telegram notification functionality');

Artisan::command('test:telegram-notification-advanced {--user=} {--url=} {--status=}', function () {
    $this->info('Testing Telegram notification (Advanced)...');

    // Get user by ID or email if specified, otherwise get first user with Telegram
    $user = null;
    if ($this->option('user')) {
        $user = User::where('id', $this->option('user'))
            ->orWhere('email', $this->option('user'))
            ->first();

        if (! $user) {
            $this->error("User not found: {$this->option('user')}");

            return 1;
        }
    } else {
        $user = User::whereHas('notificationChannels', function ($query) {
            $query->where('type', 'telegram')
                ->where('is_enabled', true);
        })->first();

        if (! $user) {
            $this->error('No user found with enabled Telegram notification channel.');
            $this->info('Please ensure you have a user with Telegram notification channel configured.');

            return 1;
        }
    }

    $this->info("Found user: {$user->name} ({$user->email})");

    // Check if user has Telegram channel enabled
    $telegramChannel = $user->notificationChannels()
        ->where('type', 'telegram')
        ->where('is_enabled', true)
        ->first();

    if (! $telegramChannel) {
        $this->error("User {$user->name} does not have enabled Telegram notification channel.");

        return 1;
    }

    $this->info("Telegram destination: {$telegramChannel->destination}");

    // Create test data with custom values
    $testData = [
        'id' => 1,
        'url' => $this->option('url') ?: 'https://example.com',
        'status' => $this->option('status') ?: 'DOWN',
    ];

    $this->info('Test data:');
    $this->info("- URL: {$testData['url']}");
    $this->info("- Status: {$testData['status']}");

    try {
        // Send test notification
        $user->notify(new MonitorStatusChanged($testData));

        $this->info('✅ Telegram notification sent successfully!');
        $this->info('Check your Telegram chat for the test message.');
    } catch (\Exception $e) {
        $this->error('❌ Failed to send Telegram notification:');
        $this->error($e->getMessage());

        return 1;
    }
})->purpose('Test Telegram notification with custom parameters');

Artisan::command('list:notification-channels', function () {
    $this->info('Listing all users with their notification channels...');

    $users = User::with('notificationChannels')->get();

    if ($users->isEmpty()) {
        $this->info('No users found.');

        return;
    }

    foreach ($users as $user) {
        $this->info("\n👤 User: {$user->name} ({$user->email})");

        if ($user->notificationChannels->isEmpty()) {
            $this->comment('  No notification channels configured');

            continue;
        }

        foreach ($user->notificationChannels as $channel) {
            $status = $channel->is_enabled ? '✅ Enabled' : '❌ Disabled';
            $this->info("  📱 {$channel->type}: {$channel->destination} - {$status}");
        }
    }

    $this->info("\n💡 Use 'php artisan test:telegram-notification' to test notifications");
})->purpose('List all users and their notification channels');

Artisan::command('telegram:rate-limit-status {--user=}', function () {
    $this->info('Checking Telegram rate limit status...');

    // Get user by ID or email if specified, otherwise get first user with Telegram
    $user = null;
    if ($this->option('user')) {
        $user = User::where('id', $this->option('user'))
            ->orWhere('email', $this->option('user'))
            ->first();

        if (! $user) {
            $this->error("User not found: {$this->option('user')}");

            return 1;
        }
    } else {
        $user = User::whereHas('notificationChannels', function ($query) {
            $query->where('type', 'telegram')
                ->where('is_enabled', true);
        })->first();

        if (! $user) {
            $this->error('No user found with enabled Telegram notification channel.');

            return 1;
        }
    }

    $this->info("User: {$user->name} ({$user->email})");

    // Get Telegram channel
    $telegramChannel = $user->notificationChannels()
        ->where('type', 'telegram')
        ->where('is_enabled', true)
        ->first();

    if (! $telegramChannel) {
        $this->error("User {$user->name} does not have enabled Telegram notification channel.");

        return 1;
    }

    $this->info("Telegram destination: {$telegramChannel->destination}");

    // Get rate limit stats
    $rateLimitService = app(\App\Services\TelegramRateLimitService::class);
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);

    $this->info("\n📊 Rate Limit Statistics:");
    $this->info("Minute count: {$stats['minute_count']}/{$stats['minute_limit']}");
    $this->info("Hour count: {$stats['hour_count']}/{$stats['hour_limit']}");
    $this->info("Backoff count: {$stats['backoff_count']}");

    if ($stats['is_in_backoff']) {
        $backoffUntil = $stats['backoff_until'] ? date('Y-m-d H:i:s', $stats['backoff_until']) : 'Unknown';
        $this->warn("⚠️  In backoff period until: {$backoffUntil}");
    } else {
        $this->info('✅ Not in backoff period');
    }

    if ($stats['minute_count'] >= $stats['minute_limit']) {
        $this->warn('⚠️  Minute rate limit reached');
    }

    if ($stats['hour_count'] >= $stats['hour_limit']) {
        $this->warn('⚠️  Hour rate limit reached');
    }

    $this->info("\n💡 Use 'php artisan test:telegram-notification' to test notifications");
})->purpose('Check Telegram rate limit status for a user');

Artisan::command('telegram:reset-rate-limit {--user=}', function () {
    $this->info('Resetting Telegram rate limit...');

    // Get user by ID or email if specified, otherwise get first user with Telegram
    $user = null;
    if ($this->option('user')) {
        $user = User::where('id', $this->option('user'))
            ->orWhere('email', $this->option('user'))
            ->first();

        if (! $user) {
            $this->error("User not found: {$this->option('user')}");

            return 1;
        }
    } else {
        $user = User::whereHas('notificationChannels', function ($query) {
            $query->where('type', 'telegram')
                ->where('is_enabled', true);
        })->first();

        if (! $user) {
            $this->error('No user found with enabled Telegram notification channel.');

            return 1;
        }
    }

    $this->info("User: {$user->name} ({$user->email})");

    // Get Telegram channel
    $telegramChannel = $user->notificationChannels()
        ->where('type', 'telegram')
        ->where('is_enabled', true)
        ->first();

    if (! $telegramChannel) {
        $this->error("User {$user->name} does not have enabled Telegram notification channel.");

        return 1;
    }

    $this->info("Telegram destination: {$telegramChannel->destination}");

    // Reset rate limit by clearing cache
    $rateLimitService = app(\App\Services\TelegramRateLimitService::class);
    $rateLimitService->resetRateLimit($user, $telegramChannel);

    $this->info("✅ Rate limit reset successfully for user {$user->name}");
    $this->info("💡 Use 'php artisan telegram:rate-limit-status --user={$user->id}' to verify");
})->purpose('Reset Telegram rate limit for a user (for testing)');

Artisan::command('uptime:calculate-daily {date?} {--monitor-id=} {--force}', function () {
    $date = $this->argument('date') ?? \Carbon\Carbon::today()->toDateString();
    $monitorId = $this->option('monitor-id');
    $force = $this->option('force');

    // Validate date format
    try {
        \Carbon\Carbon::createFromFormat('Y-m-d', $date);
    } catch (\Exception $e) {
        $this->error("Invalid date format: {$date}. Please use Y-m-d format (e.g., 2024-01-15)");
        return 1;
    }

    $this->info("Starting daily uptime calculation for date: {$date}");

    try {
        if ($monitorId) {
            // Calculate for specific monitor
            $monitor = \App\Models\Monitor::find($monitorId);
            if (!$monitor) {
                $this->error("Monitor with ID {$monitorId} not found");
                return 1;
            }

            $this->info("Calculating uptime for monitor: {$monitor->url} (ID: {$monitorId})");

            // Check if calculation already exists (unless force is used)
            if (!$force && \DB::table('monitor_uptime_dailies')
                ->where('monitor_id', $monitorId)
                ->where('date', $date)
                ->exists()) {
                $this->warn("Uptime calculation for monitor {$monitorId} on {$date} already exists. Use --force to recalculate.");
                return 0;
            }

            // Dispatch single monitor calculation job
            $job = new \App\Jobs\CalculateSingleMonitorUptimeJob((int) $monitorId, $date);
            dispatch($job);

            $this->info("Job dispatched for monitor {$monitorId} for date {$date}");
        } else {
            // Calculate for all monitors
            $this->info("Calculating uptime for all monitors for date: {$date}");

            $monitorIds = \App\Models\Monitor::pluck('id')->toArray();

            if (empty($monitorIds)) {
                $this->warn('No monitors found for uptime calculation');
                return 0;
            }

            $this->info("Found " . count($monitorIds) . " monitors to process");

            // If force is used, we'll process all monitors
            // Otherwise, we'll skip monitors that already have calculations
            $monitorsToProcess = $force ? $monitorIds : array_diff(
                $monitorIds,
                \DB::table('monitor_uptime_dailies')
                    ->whereIn('monitor_id', $monitorIds)
                    ->where('date', $date)
                    ->pluck('monitor_id')
                    ->toArray()
            );

            if (empty($monitorsToProcess)) {
                $this->info('All monitors already have uptime calculations for this date. Use --force to recalculate.');
                return 0;
            }

            $this->info("Processing " . count($monitorsToProcess) . " monitors");

            // Dispatch jobs for each monitor
            foreach ($monitorsToProcess as $monitorId) {
                $job = new \App\Jobs\CalculateSingleMonitorUptimeJob($monitorId, $date);
                dispatch($job);
            }

            $this->info("Dispatched " . count($monitorsToProcess) . " calculation jobs");
        }

        $this->info('Daily uptime calculation job dispatched successfully!');
        return 0;

    } catch (\Exception $e) {
        $this->error("Failed to dispatch uptime calculation job: {$e->getMessage()}");
        \Illuminate\Support\Facades\Log::error('CalculateDailyUptimeCommand failed', [
            'date' => $date,
            'monitor_id' => $monitorId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return 1;
    }
})->purpose('Calculate daily uptime for all monitors or a specific monitor for a given date');
*/
