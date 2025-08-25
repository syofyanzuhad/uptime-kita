<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\Services\TelegramRateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TelegramRateLimitService::class);
    $this->user = User::factory()->create();
    $this->telegramChannel = NotificationChannel::factory()->create([
        'type' => 'telegram',
        'destination' => '123456789',
        'user_id' => $this->user->id,
    ]);
    
    // Clear cache before each test
    Cache::flush();
});

describe('TelegramRateLimitService', function () {
    describe('shouldSendNotification', function () {
        it('allows notification when no rate limits are hit', function () {
            $result = $this->service->shouldSendNotification($this->user, $this->telegramChannel);
            
            expect($result)->toBeTrue();
        });

        it('throws exception for non-telegram channel', function () {
            $emailChannel = NotificationChannel::factory()->create([
                'type' => 'email',
                'destination' => 'test@example.com',
                'user_id' => $this->user->id,
            ]);
            
            expect(fn() => $this->service->shouldSendNotification($this->user, $emailChannel))
                ->toThrow(InvalidArgumentException::class, 'NotificationChannel must be of type telegram');
        });

        it('blocks notification when in backoff period', function () {
            // Set up backoff period
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'backoff_until' => now()->addMinutes(30)->timestamp,
                'backoff_count' => 1,
                'minute_count' => 0,
                'hour_count' => 0,
            ], 120);
            
            $result = $this->service->shouldSendNotification($this->user, $this->telegramChannel);
            
            expect($result)->toBeFalse();
        });

        it('blocks notification when minute rate limit is exceeded', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'minute_count' => 21, // Over the 20 limit
                'minute_window_start' => now()->timestamp,
                'hour_count' => 10,
                'hour_window_start' => now()->timestamp,
            ], 120);
            
            $result = $this->service->shouldSendNotification($this->user, $this->telegramChannel);
            
            expect($result)->toBeFalse();
        });

        it('blocks notification when hour rate limit is exceeded', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'minute_count' => 10,
                'minute_window_start' => now()->timestamp,
                'hour_count' => 101, // Over the 100 limit
                'hour_window_start' => now()->timestamp,
            ], 120);
            
            $result = $this->service->shouldSendNotification($this->user, $this->telegramChannel);
            
            expect($result)->toBeFalse();
        });

        it('allows notification when minute window has expired', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'minute_count' => 21, // Over limit but old window
                'minute_window_start' => now()->subMinutes(2)->timestamp,
                'hour_count' => 10,
                'hour_window_start' => now()->timestamp,
            ], 120);
            
            $result = $this->service->shouldSendNotification($this->user, $this->telegramChannel);
            
            expect($result)->toBeTrue();
        });

        it('allows notification when hour window has expired', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'minute_count' => 10,
                'minute_window_start' => now()->timestamp,
                'hour_count' => 101, // Over limit but old window
                'hour_window_start' => now()->subHours(2)->timestamp,
            ], 120);
            
            $result = $this->service->shouldSendNotification($this->user, $this->telegramChannel);
            
            expect($result)->toBeTrue();
        });
    });

    describe('trackSuccessfulNotification', function () {
        it('increments counters correctly', function () {
            $this->service->trackSuccessfulNotification($this->user, $this->telegramChannel);
            
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($stats['minute_count'])->toBe(1);
            expect($stats['hour_count'])->toBe(1);
            expect($stats['backoff_count'])->toBe(0);
        });

        it('resets backoff on successful notification', function () {
            // Set up initial backoff
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'backoff_until' => now()->addMinutes(30)->timestamp,
                'backoff_count' => 2,
                'minute_count' => 0,
                'hour_count' => 0,
            ], 120);
            
            $this->service->trackSuccessfulNotification($this->user, $this->telegramChannel);
            
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($stats['backoff_count'])->toBe(0);
            expect($stats['backoff_until'])->toBeNull();
            expect($stats['is_in_backoff'])->toBeFalse();
        });

        it('resets minute counter when window expires', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'minute_count' => 15,
                'minute_window_start' => now()->subMinutes(2)->timestamp,
                'hour_count' => 5,
                'hour_window_start' => now()->timestamp,
            ], 120);
            
            $this->service->trackSuccessfulNotification($this->user, $this->telegramChannel);
            
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($stats['minute_count'])->toBe(1); // Reset to 1
            expect($stats['hour_count'])->toBe(6); // Incremented from 5
        });
    });

    describe('trackFailedNotification', function () {
        it('sets initial backoff period', function () {
            $this->service->trackFailedNotification($this->user, $this->telegramChannel);
            
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($stats['backoff_count'])->toBe(1);
            expect($stats['backoff_until'])->not->toBeNull();
            expect($stats['is_in_backoff'])->toBeTrue();
        });

        it('increases backoff period exponentially', function () {
            // First failure
            $this->service->trackFailedNotification($this->user, $this->telegramChannel);
            $stats1 = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            
            // Second failure
            $this->service->trackFailedNotification($this->user, $this->telegramChannel);
            $stats2 = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            
            expect($stats2['backoff_count'])->toBe(2);
            expect($stats2['backoff_until'])->toBeGreaterThan($stats1['backoff_until']);
        });

        it('caps backoff period at maximum', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'backoff_count' => 10, // Very high count
                'minute_count' => 0,
                'hour_count' => 0,
            ], 120);
            
            $this->service->trackFailedNotification($this->user, $this->telegramChannel);
            
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($stats['backoff_count'])->toBe(11);
            expect($stats['backoff_until'])->toBeLessThan(now()->addMinutes(61)->timestamp);
        });
    });

    describe('getRateLimitStats', function () {
        it('returns default stats for new user', function () {
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            
            expect($stats)->toMatchArray([
                'minute_count' => 0,
                'hour_count' => 0,
                'backoff_count' => 0,
                'backoff_until' => null,
                'is_in_backoff' => false,
                'minute_limit' => 20,
                'hour_limit' => 100,
            ]);
        });

        it('returns accurate stats with data', function () {
            $cacheKey = "telegram_rate_limit:{$this->user->id}:{$this->telegramChannel->destination}";
            Cache::put($cacheKey, [
                'minute_count' => 5,
                'hour_count' => 25,
                'backoff_count' => 2,
                'backoff_until' => now()->addMinutes(30)->timestamp,
            ], 120);
            
            $stats = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            
            expect($stats['minute_count'])->toBe(5);
            expect($stats['hour_count'])->toBe(25);
            expect($stats['backoff_count'])->toBe(2);
            expect($stats['is_in_backoff'])->toBeTrue();
        });
    });

    describe('resetRateLimit', function () {
        it('clears all rate limit data', function () {
            // Set up some rate limit data
            $this->service->trackSuccessfulNotification($this->user, $this->telegramChannel);
            $this->service->trackFailedNotification($this->user, $this->telegramChannel);
            
            // Verify data exists
            $statsBeforeReset = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($statsBeforeReset['minute_count'])->toBeGreaterThan(0);
            
            // Reset
            $this->service->resetRateLimit($this->user, $this->telegramChannel);
            
            // Verify data is cleared
            $statsAfterReset = $this->service->getRateLimitStats($this->user, $this->telegramChannel);
            expect($statsAfterReset['minute_count'])->toBe(0);
            expect($statsAfterReset['hour_count'])->toBe(0);
            expect($statsAfterReset['backoff_count'])->toBe(0);
            expect($statsAfterReset['is_in_backoff'])->toBeFalse();
        });
    });
});