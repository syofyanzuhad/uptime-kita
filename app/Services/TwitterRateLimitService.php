<?php

namespace App\Services;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TwitterRateLimitService
{
    private const RATE_LIMIT_PER_HOUR = 30;

    private const RATE_LIMIT_PER_DAY = 200;

    private const CACHE_TTL_HOURS = 24;

    private const BACKOFF_MULTIPLIER = 2;

    private const MAX_BACKOFF_MINUTES = 60;

    public function shouldSendNotification(User $user, ?NotificationChannel $channel = null): bool
    {
        $backoffKey = $this->getBackoffCacheKey($user, $channel);
        $backoffData = Cache::get($backoffKey, []);

        // Check if we're in a backoff period
        if (isset($backoffData['backoff_until']) && now()->timestamp < $backoffData['backoff_until']) {
            Log::info('Twitter notification blocked due to backoff period', [
                'user_id' => $user->id,
                'channel_id' => $channel?->id ?? 'system',
                'backoff_until' => $backoffData['backoff_until'],
            ]);

            return false;
        }

        $hourlyKey = $this->getHourlyCacheKey($user, $channel);
        $dailyKey = $this->getDailyCacheKey($user, $channel);

        $hourlyCount = Cache::get($hourlyKey, 0);
        $dailyCount = Cache::get($dailyKey, 0);

        if ($hourlyCount >= self::RATE_LIMIT_PER_HOUR) {
            Log::warning('Twitter hourly rate limit reached', [
                'user_id' => $user->id,
                'channel_id' => $channel?->id ?? 'system',
                'hourly_count' => $hourlyCount,
            ]);

            return false;
        }

        if ($dailyCount >= self::RATE_LIMIT_PER_DAY) {
            Log::warning('Twitter daily rate limit reached', [
                'user_id' => $user->id,
                'channel_id' => $channel?->id ?? 'system',
                'daily_count' => $dailyCount,
            ]);

            return false;
        }

        return true;
    }

    public function trackSuccessfulNotification(User $user, ?NotificationChannel $channel = null): void
    {
        $hourlyKey = $this->getHourlyCacheKey($user, $channel);
        $dailyKey = $this->getDailyCacheKey($user, $channel);

        Cache::increment($hourlyKey);
        Cache::increment($dailyKey);

        // Set expiration if not set
        if (! Cache::has($hourlyKey)) {
            Cache::put($hourlyKey, 1, now()->addHour());
        }
        if (! Cache::has($dailyKey)) {
            Cache::put($dailyKey, 1, now()->addDay());
        }

        // Reset backoff on successful notification
        $backoffKey = $this->getBackoffCacheKey($user, $channel);
        Cache::forget($backoffKey);
    }

    /**
     * Track a failed notification (429 error)
     */
    public function trackFailedNotification(User $user, ?NotificationChannel $channel = null): void
    {
        $backoffKey = $this->getBackoffCacheKey($user, $channel);
        $backoffData = Cache::get($backoffKey, [
            'backoff_count' => 0,
        ]);

        $currentTime = now()->timestamp;
        $backoffCount = ($backoffData['backoff_count'] ?? 0) + 1;

        // Cap backoff count to prevent overflow (2^6 = 64, which is > MAX_BACKOFF_MINUTES)
        $cappedCount = min($backoffCount, 6);
        $backoffMinutes = min(self::BACKOFF_MULTIPLIER ** $cappedCount, self::MAX_BACKOFF_MINUTES);

        $backoffData['backoff_until'] = $currentTime + ($backoffMinutes * 60);
        $backoffData['backoff_count'] = $backoffCount;

        // Store backoff data for 24 hours (or at least MAX_BACKOFF_MINUTES)
        Cache::put($backoffKey, $backoffData, now()->addHours(self::CACHE_TTL_HOURS));

        Log::warning('Twitter notification failed - backoff period set', [
            'user_id' => $user->id,
            'channel_id' => $channel?->id ?? 'system',
            'backoff_count' => $backoffCount,
            'backoff_minutes' => $backoffMinutes,
            'backoff_until' => $backoffData['backoff_until'],
        ]);
    }

    public function getRemainingTweets(User $user, ?NotificationChannel $channel = null): array
    {
        $hourlyKey = $this->getHourlyCacheKey($user, $channel);
        $dailyKey = $this->getDailyCacheKey($user, $channel);

        $hourlyCount = Cache::get($hourlyKey, 0);
        $dailyCount = Cache::get($dailyKey, 0);

        return [
            'hourly_remaining' => max(0, self::RATE_LIMIT_PER_HOUR - $hourlyCount),
            'daily_remaining' => max(0, self::RATE_LIMIT_PER_DAY - $dailyCount),
        ];
    }

    private function getHourlyCacheKey(User $user, ?NotificationChannel $channel = null): string
    {
        $channelId = $channel?->id ?? 'system';

        return sprintf('twitter_rate_limit:hourly:%d:%s', $user->id, $channelId);
    }

    private function getDailyCacheKey(User $user, ?NotificationChannel $channel = null): string
    {
        $channelId = $channel?->id ?? 'system';

        return sprintf('twitter_rate_limit:daily:%d:%s', $user->id, $channelId);
    }

    private function getBackoffCacheKey(User $user, ?NotificationChannel $channel = null): string
    {
        $channelId = $channel?->id ?? 'system';

        return sprintf('twitter_rate_limit:backoff:%d:%s', $user->id, $channelId);
    }
}
