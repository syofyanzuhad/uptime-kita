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

    public function shouldSendNotification(User $user, NotificationChannel $channel): bool
    {
        $hourlyKey = $this->getHourlyCacheKey($user, $channel);
        $dailyKey = $this->getDailyCacheKey($user, $channel);

        $hourlyCount = Cache::get($hourlyKey, 0);
        $dailyCount = Cache::get($dailyKey, 0);

        if ($hourlyCount >= self::RATE_LIMIT_PER_HOUR) {
            Log::warning('Twitter hourly rate limit reached', [
                'user_id' => $user->id,
                'channel_id' => $channel->id,
                'hourly_count' => $hourlyCount,
            ]);

            return false;
        }

        if ($dailyCount >= self::RATE_LIMIT_PER_DAY) {
            Log::warning('Twitter daily rate limit reached', [
                'user_id' => $user->id,
                'channel_id' => $channel->id,
                'daily_count' => $dailyCount,
            ]);

            return false;
        }

        return true;
    }

    public function trackSuccessfulNotification(User $user, NotificationChannel $channel): void
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
    }

    public function getRemainingTweets(User $user, NotificationChannel $channel): array
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

    private function getHourlyCacheKey(User $user, NotificationChannel $channel): string
    {
        return sprintf('twitter_rate_limit:hourly:%d:%d', $user->id, $channel->id);
    }

    private function getDailyCacheKey(User $user, NotificationChannel $channel): string
    {
        return sprintf('twitter_rate_limit:daily:%d:%d', $user->id, $channel->id);
    }
}
