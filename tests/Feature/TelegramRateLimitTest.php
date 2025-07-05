<?php

use App\Models\User;
use App\Models\NotificationChannel;
use App\Services\TelegramRateLimitService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Clear any existing cache
    Cache::flush();
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

    // Simulate time passing (manually clear backoff)
    $cacheKey = "telegram_rate_limit:{$user->id}:{$telegramChannel->destination}";
    $data = Cache::get($cacheKey);
    unset($data['backoff_until']);
    unset($data['backoff_count']);
    Cache::put($cacheKey, $data, 120);

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
