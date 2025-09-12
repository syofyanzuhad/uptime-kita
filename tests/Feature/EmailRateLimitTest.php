<?php

use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use App\Services\EmailRateLimitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // Clean up any existing email logs
    DB::table('email_notification_logs')->truncate();
});

it('can send email when under daily limit', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    expect($service->canSendEmail($user, 'test_notification'))->toBeTrue();
    expect($service->getRemainingEmailCount($user))->toBe(10);
});

it('blocks email when daily limit is reached', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    // Send 10 emails (the daily limit)
    for ($i = 0; $i < 10; $i++) {
        $service->logEmailSent($user, 'test_notification', ['test' => $i]);
    }

    expect($service->canSendEmail($user, 'test_notification'))->toBeFalse();
    expect($service->getRemainingEmailCount($user))->toBe(0);
    expect($service->getTodayEmailCount($user))->toBe(10);
});

it('logs email notifications correctly', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    $service->logEmailSent($user, 'monitor_status_changed', [
        'monitor_id' => 123,
        'status' => 'DOWN',
    ]);

    $log = DB::table('email_notification_logs')
        ->where('user_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->email)->toBe($user->email);
    expect($log->notification_type)->toBe('monitor_status_changed');
    expect($log->sent_date)->toBe(Carbon::today()->toDateString());

    $data = json_decode($log->notification_data, true);
    expect($data['monitor_id'])->toBe(123);
    expect($data['status'])->toBe('DOWN');
});

it('resets count for new day', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    // Log emails for yesterday
    $yesterday = Carbon::yesterday();
    for ($i = 0; $i < 10; $i++) {
        DB::table('email_notification_logs')->insert([
            'user_id' => $user->id,
            'email' => $user->email,
            'notification_type' => 'test',
            'notification_data' => json_encode([]),
            'sent_date' => $yesterday->toDateString(),
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);
    }

    // Check today's count (should be 0 since all emails were yesterday)
    expect($service->canSendEmail($user, 'test_notification'))->toBeTrue();
    expect($service->getTodayEmailCount($user))->toBe(0);
    expect($service->getRemainingEmailCount($user))->toBe(10);
});

it('detects when user is approaching limit', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    // Not approaching limit with 7 emails
    for ($i = 0; $i < 7; $i++) {
        $service->logEmailSent($user, 'test_notification');
    }
    expect($service->isApproachingLimit($user))->toBeFalse();

    // Approaching limit with 8 emails (80% of 10)
    $service->logEmailSent($user, 'test_notification');
    expect($service->isApproachingLimit($user))->toBeTrue();
});

it('different users have separate limits', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $service = new EmailRateLimitService;

    // Max out user1's limit
    for ($i = 0; $i < 10; $i++) {
        $service->logEmailSent($user1, 'test_notification');
    }

    // User2 should still be able to send
    expect($service->canSendEmail($user1, 'test_notification'))->toBeFalse();
    expect($service->canSendEmail($user2, 'test_notification'))->toBeTrue();
    expect($service->getRemainingEmailCount($user2))->toBe(10);
});

it('notification skips email channel when rate limited', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    // Create notification channels for the user
    DB::table('notification_channels')->insert([
        'user_id' => $user->id,
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Max out the email limit
    for ($i = 0; $i < 10; $i++) {
        $service->logEmailSent($user, 'test_notification');
    }

    // Create a notification
    $notification = new MonitorStatusChanged([
        'id' => 1,
        'url' => 'https://example.com',
        'status' => 'DOWN',
        'message' => 'Site is down',
    ]);

    // Get channels - email should be filtered out due to rate limiting
    $channels = $notification->via($user);

    expect($channels)->not->toContain('mail');
});

it('cleans up old logs correctly', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    // Create old logs (31 days ago)
    $oldDate = Carbon::now()->subDays(31);
    DB::table('email_notification_logs')->insert([
        'user_id' => $user->id,
        'email' => $user->email,
        'notification_type' => 'old_notification',
        'sent_date' => $oldDate->toDateString(),
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ]);

    // Create recent log
    $service->logEmailSent($user, 'recent_notification');

    // Clean up logs older than 30 days
    $deletedCount = $service->cleanupOldLogs(30);

    expect($deletedCount)->toBe(1);
    expect(DB::table('email_notification_logs')->count())->toBe(1);

    $remainingLog = DB::table('email_notification_logs')->first();
    expect($remainingLog->notification_type)->toBe('recent_notification');
});

it('adds warning message when approaching limit', function () {
    $user = User::factory()->create();
    $service = new EmailRateLimitService;

    // Send 8 emails to approach the limit
    for ($i = 0; $i < 8; $i++) {
        $service->logEmailSent($user, 'test_notification');
    }

    // Create notification
    $notification = new MonitorStatusChanged([
        'id' => 1,
        'url' => 'https://example.com',
        'status' => 'DOWN',
        'message' => 'Site is down',
    ]);

    $mailMessage = $notification->toMail($user);

    // Check that warning is included in the message
    expect($mailMessage)->not->toBeNull();

    // The service should have logged one more email when toMail was called
    expect($service->getTodayEmailCount($user))->toBe(9);
    expect($service->getRemainingEmailCount($user))->toBe(1);

    // Convert to array to check content
    $messageData = $mailMessage->toArray();
    $allLines = array_merge(
        $messageData['introLines'] ?? [],
        $messageData['outroLines'] ?? []
    );

    $foundWarning = false;
    foreach ($allLines as $line) {
        if (str_contains($line, 'Anda memiliki 1 email notifikasi tersisa')) {
            $foundWarning = true;
            break;
        }
    }

    expect($foundWarning)->toBeTrue();
});
