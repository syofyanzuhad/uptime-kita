<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\NotificationChannel;

class TelegramRateLimitService
{
    private const MAX_MESSAGES_PER_MINUTE = 20;
    private const MAX_MESSAGES_PER_HOUR = 100;
    private const CACHE_TTL = 120; // 2 minutes
    private const BACKOFF_MULTIPLIER = 2;
    private const MAX_BACKOFF_MINUTES = 60;

    /**
     * Check if Telegram notification should be sent
     */
public function shouldSendNotification(User $user, NotificationChannel $telegramChannel): bool
{
    if ($telegramChannel->type !== 'telegram') {
        throw new \InvalidArgumentException('NotificationChannel must be of type telegram');
    }

    $rateLimitKey = $this->getRateLimitKey($user, $telegramChannel);
    $rateLimitData = $this->getRateLimitData($rateLimitKey);

    // Check if we're in a backoff period
    if ($this->isInBackoffPeriod($rateLimitData)) {
        Log::info('Telegram notification blocked due to backoff period', [
            'user_id' => $user->id,
            'telegram_destination' => $telegramChannel->destination,
            'backoff_until' => $rateLimitData['backoff_until'] ?? null,
        ]);
        return false;
    }

    // …rest of method…
}

        // Check minute rate limit
        if (!$this->checkMinuteRateLimit($rateLimitData)) {
            Log::info('Telegram notification blocked due to minute rate limit', [
                'user_id' => $user->id,
                'telegram_destination' => $telegramChannel->destination,
                'minute_count' => $rateLimitData['minute_count'] ?? 0,
            ]);
            return false;
        }

        // Check hour rate limit
        if (!$this->checkHourRateLimit($rateLimitData)) {
            Log::info('Telegram notification blocked due to hour rate limit', [
                'user_id' => $user->id,
                'telegram_destination' => $telegramChannel->destination,
                'hour_count' => $rateLimitData['hour_count'] ?? 0,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Track a successful notification
     */
    public function trackSuccessfulNotification(User $user, NotificationChannel $telegramChannel): void
    {
        $rateLimitKey = $this->getRateLimitKey($user, $telegramChannel);
        $rateLimitData = $this->getRateLimitData($rateLimitKey);

        $currentTime = now()->timestamp;

        // Update minute counter
        $rateLimitData = $this->updateMinuteCounter($rateLimitData, $currentTime);

        // Update hour counter
        $rateLimitData = $this->updateHourCounter($rateLimitData, $currentTime);

        // Reset backoff on successful notification
        unset($rateLimitData['backoff_until']);
        unset($rateLimitData['backoff_count']);

        $this->storeRateLimitData($rateLimitKey, $rateLimitData);

        Log::info('Telegram notification tracked successfully', [
            'user_id' => $user->id,
            'telegram_destination' => $telegramChannel->destination,
            'minute_count' => $rateLimitData['minute_count'],
            'hour_count' => $rateLimitData['hour_count'],
        ]);
    }

    /**
     * Track a failed notification (429 error)
     */
    public function trackFailedNotification(User $user, NotificationChannel $telegramChannel): void
    {
        $rateLimitKey = $this->getRateLimitKey($user, $telegramChannel);
        $rateLimitData = $this->getRateLimitData($rateLimitKey);

        $currentTime = now()->timestamp;
        $currentTime = now()->timestamp;
        $backoffCount = ($rateLimitData['backoff_count'] ?? 0) + 1;
        // Cap backoff count to prevent overflow (2^6 = 64, which is > MAX_BACKOFF_MINUTES)
        $cappedCount = min($backoffCount, 6);
        $backoffMinutes = min(self::BACKOFF_MULTIPLIER ** $cappedCount, self::MAX_BACKOFF_MINUTES);
        $rateLimitData['backoff_until'] = $currentTime + ($backoffMinutes * 60);
        $rateLimitData['backoff_count'] = $backoffCount;

        $this->storeRateLimitData($rateLimitKey, $rateLimitData);

        Log::warning('Telegram notification failed - backoff period set', [
            'user_id' => $user->id,
            'telegram_destination' => $telegramChannel->destination,
            'backoff_count' => $backoffCount,
            'backoff_minutes' => $backoffMinutes,
            'backoff_until' => $rateLimitData['backoff_until'],
        ]);
    }

    /**
     * Get rate limit statistics
     */
    public function getRateLimitStats(User $user, NotificationChannel $telegramChannel): array
    {
        $rateLimitKey = $this->getRateLimitKey($user, $telegramChannel);
        $rateLimitData = $this->getRateLimitData($rateLimitKey);

        return [
            'minute_count' => $rateLimitData['minute_count'] ?? 0,
            'hour_count' => $rateLimitData['hour_count'] ?? 0,
            'backoff_count' => $rateLimitData['backoff_count'] ?? 0,
            'backoff_until' => $rateLimitData['backoff_until'] ?? null,
            'is_in_backoff' => $this->isInBackoffPeriod($rateLimitData),
            'minute_limit' => self::MAX_MESSAGES_PER_MINUTE,
            'hour_limit' => self::MAX_MESSAGES_PER_HOUR,
        ];
    }

    /**
     * Get the cache key for rate limiting
     */
    private function getRateLimitKey(User $user, NotificationChannel $telegramChannel): string
    {
        return "telegram_rate_limit:{$user->id}:{$telegramChannel->destination}";
    }

    /**
     * Get rate limit data from cache
     */
    private function getRateLimitData(string $key): array
    {
        return Cache::get($key, [
            'minute_count' => 0,
            'minute_window_start' => now()->timestamp,
            'hour_count' => 0,
            'hour_window_start' => now()->timestamp,
            'backoff_count' => 0,
        ]);
    }

    /**
     * Store rate limit data in cache
     */
    private function storeRateLimitData(string $key, array $data): void
    {
        Cache::put($key, $data, self::CACHE_TTL);
    }

    /**
     * Check if we're in a backoff period
     */
    private function isInBackoffPeriod(array $rateLimitData): bool
    {
        if (!isset($rateLimitData['backoff_until'])) {
            return false;
        }

        return now()->timestamp < $rateLimitData['backoff_until'];
    }

    /**
     * Check minute rate limit
     */
    private function checkMinuteRateLimit(array $rateLimitData): bool
    {
        $currentTime = now()->timestamp;
        $windowStart = $rateLimitData['minute_window_start'] ?? $currentTime;
        $count = $rateLimitData['minute_count'] ?? 0;

        // Reset window if more than 1 minute has passed
        if ($currentTime - $windowStart >= 60) {
            return true;
        }

        return $count < self::MAX_MESSAGES_PER_MINUTE;
    }

    /**
     * Check hour rate limit
     */
    private function checkHourRateLimit(array $rateLimitData): bool
    {
        $currentTime = now()->timestamp;
        $windowStart = $rateLimitData['hour_window_start'] ?? $currentTime;
        $count = $rateLimitData['hour_count'] ?? 0;

        // Reset window if more than 1 hour has passed
        if ($currentTime - $windowStart >= 3600) {
            return true;
        }

        return $count < self::MAX_MESSAGES_PER_HOUR;
    }

    /**
     * Update minute counter
     */
    private function updateMinuteCounter(array $rateLimitData, int $currentTime): array
    {
        $windowStart = $rateLimitData['minute_window_start'] ?? $currentTime;

        // Reset window if more than 1 minute has passed
        if ($currentTime - $windowStart >= 60) {
            $rateLimitData['minute_count'] = 1;
            $rateLimitData['minute_window_start'] = $currentTime;
        } else {
            $rateLimitData['minute_count'] = ($rateLimitData['minute_count'] ?? 0) + 1;
        }

        return $rateLimitData;
    }

    /**
     * Update hour counter
     */
    private function updateHourCounter(array $rateLimitData, int $currentTime): array
    {
        $windowStart = $rateLimitData['hour_window_start'] ?? $currentTime;

        // Reset window if more than 1 hour has passed
        if ($currentTime - $windowStart >= 3600) {
            $rateLimitData['hour_count'] = 1;
            $rateLimitData['hour_window_start'] = $currentTime;
        } else {
            $rateLimitData['hour_count'] = ($rateLimitData['hour_count'] ?? 0) + 1;
        }

        return $rateLimitData;
    }
}
