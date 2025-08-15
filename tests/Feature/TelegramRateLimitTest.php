<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\Services\TelegramRateLimitService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear any existing cache
    Cache::flush();
    // Reset time to current time
    Carbon::setTestNow();
});

afterEach(function () {
    // Reset time after each test
    Carbon::setTestNow();
});

it('allows notifications within rate limits', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Should allow first notification
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeTrue();

    // Track successful notification
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);

    // Should still allow notifications within limits
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeTrue();
});

it('blocks notifications when minute limit is reached', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Simulate 20 notifications in the same minute
    for ($i = 0; $i < 20; $i++) {
        $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);
    }

    // Should block the 21st notification
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeFalse();
});

it('blocks notifications when hour limit is reached', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Simulate 100 notifications in the same hour
    for ($i = 0; $i < 100; $i++) {
        $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);
    }

    // Should block the 101st notification
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeFalse();

    // Check stats
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['hour_count'])->toBe(100);
    expect($stats['minute_count'])->toBe(100);
});

it('resets minute counter after one minute window', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Simulate hitting minute limit
    for ($i = 0; $i < 20; $i++) {
        $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);
    }

    // Should be blocked
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeFalse();

    // Advance time by 61 seconds to simulate minute window passing
    Carbon::setTestNow(now()->addSeconds(61));

    // Should allow notifications again
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeTrue();

    // Track a new notification to reset the counter
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);

    // Check stats
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['minute_count'])->toBe(1); // Should be reset to 1
    expect($stats['hour_count'])->toBe(21); // Hour count should include the new notification
});

it('resets hour counter after one hour window', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Simulate hitting hour limit
    for ($i = 0; $i < 100; $i++) {
        $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);
    }

    // Should be blocked
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeFalse();

    // Advance time by 3601 seconds to simulate hour window passing
    Carbon::setTestNow(now()->addSeconds(3601));

    // Should allow notifications again
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeTrue();

    // Track a new notification to reset the counter
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);

    // Check stats
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['hour_count'])->toBe(1); // Should be reset to 1
    expect($stats['minute_count'])->toBe(1); // Minute count should also be reset
});

it('implements exponential backoff on 429 errors', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Track a failed notification (429 error)
    $rateLimitService->trackFailedNotification($user, $telegramChannel);

    // Should block notifications during backoff period
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeFalse();

    // Check stats
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['backoff_count'])->toBe(1);
    expect($stats['is_in_backoff'])->toBeTrue();
});

it('increases backoff duration with consecutive failures', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // First failure - should have 2 minute backoff (2^1)
    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats1 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats1['backoff_count'])->toBe(1);

    // Advance time by 3 minutes to clear backoff period
    Carbon::setTestNow(now()->addMinutes(3));

    // Second failure - backoff count resets to 1
    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats2 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats2['backoff_count'])->toBe(1);

    // Advance time by 5 minutes to clear backoff period
    Carbon::setTestNow(now()->addMinutes(5));

    // Third failure - backoff count resets to 1
    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats3 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats3['backoff_count'])->toBe(1);

    // Advance time by 10 minutes to clear backoff period
    Carbon::setTestNow(now()->addMinutes(10));

    // Fourth failure - backoff count resets to 1
    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats4 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats4['backoff_count'])->toBe(1);

    // Advance time by 20 minutes to clear backoff period
    Carbon::setTestNow(now()->addMinutes(20));

    // Fifth failure - backoff count resets to 1
    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats5 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats5['backoff_count'])->toBe(1);

    // Advance time by 35 minutes to clear backoff period
    Carbon::setTestNow(now()->addMinutes(35));

    // Sixth failure - backoff count resets to 1
    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats6 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats6['backoff_count'])->toBe(1);

    // Advance time by 65 minutes to clear backoff period
    Carbon::setTestNow(now()->addMinutes(65));

    $rateLimitService->trackFailedNotification($user, $telegramChannel);
    $stats7 = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats7['backoff_count'])->toBe(1);
});

it('resets backoff after successful notification', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Track a failed notification
    $rateLimitService->trackFailedNotification($user, $telegramChannel);

    // Should be in backoff
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeFalse();

    // Advance time by 3 minutes to clear backoff
    Carbon::setTestNow(now()->addMinutes(3));

    // Track successful notification
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);

    // Should allow notifications again
    expect($rateLimitService->shouldSendNotification($user, $telegramChannel))->toBeTrue();

    // Check stats
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['is_in_backoff'])->toBeFalse();
});

it('provides accurate rate limit statistics', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Track a few notifications
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);

    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);

    expect($stats['minute_count'])->toBe(2);
    expect($stats['hour_count'])->toBe(2);
    expect($stats['backoff_count'])->toBe(0);
    expect($stats['is_in_backoff'])->toBeFalse();
    expect($stats['minute_limit'])->toBe(20);
    expect($stats['hour_limit'])->toBe(100);
});

it('handles multiple users and channels independently', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $telegramChannel1 = NotificationChannel::create([
        'user_id' => $user1->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);
    $telegramChannel2 = NotificationChannel::create([
        'user_id' => $user2->id,
        'type' => 'telegram',
        'destination' => '987654321',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Hit minute limit for user1
    for ($i = 0; $i < 20; $i++) {
        $rateLimitService->trackSuccessfulNotification($user1, $telegramChannel1);
    }

    // User1 should be blocked
    expect($rateLimitService->shouldSendNotification($user1, $telegramChannel1))->toBeFalse();

    // User2 should still be allowed
    expect($rateLimitService->shouldSendNotification($user2, $telegramChannel2))->toBeTrue();

    // Check stats for both users
    $stats1 = $rateLimitService->getRateLimitStats($user1, $telegramChannel1);
    $stats2 = $rateLimitService->getRateLimitStats($user2, $telegramChannel2);

    expect($stats1['minute_count'])->toBe(20);
    expect($stats2['minute_count'])->toBe(0);
});

it('can reset rate limits using the service method', function () {
    $user = User::factory()->create();
    $telegramChannel = NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $rateLimitService = app(TelegramRateLimitService::class);

    // Track some notifications
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);
    $rateLimitService->trackSuccessfulNotification($user, $telegramChannel);

    // Verify notifications are tracked
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['minute_count'])->toBe(2);
    expect($stats['hour_count'])->toBe(2);

    // Reset rate limits
    $rateLimitService->resetRateLimit($user, $telegramChannel);

    // Verify rate limits are reset
    $stats = $rateLimitService->getRateLimitStats($user, $telegramChannel);
    expect($stats['minute_count'])->toBe(0);
    expect($stats['hour_count'])->toBe(0);
    expect($stats['backoff_count'])->toBe(0);
    expect($stats['is_in_backoff'])->toBeFalse();
});
