<?php

use App\Jobs\CalculateMonitorUptimeDailyJob;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\UptimeMonitor\Commands\CheckCertificates;
use Spatie\UptimeMonitor\Commands\CheckUptime;

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

Schedule::command(CheckUptime::class)->everyTwoMinutes();
Schedule::command(CheckCertificates::class)->daily();

// === LARAVEL HORIZON ===
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('horizon:forget --all')->daily();

// === LARAVEL TELOSCOPE ===
Schedule::command('telescope:prune --hours=48')->everyFiveMinutes();

// === LARAVEL PRUNABLE MODELS ===
Schedule::command('model:prune')->daily();

Schedule::job(new CalculateMonitorUptimeDailyJob)->everyFiveMinutes();
// Schedule::job(new CalculateMonitorUptimeJob('WEEKLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('MONTHLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('YEARLY'))->hourly();
// Schedule::job(new CalculateMonitorUptimeJob('ALL'))->hourly();
