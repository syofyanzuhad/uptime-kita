<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailRateLimitService
{
    private const DAILY_EMAIL_LIMIT = 10;

    /**
     * Check if the user can send an email notification
     */
    public function canSendEmail(User $user, string $notificationType): bool
    {
        $today = Carbon::today()->toDateString();

        $count = DB::table('email_notification_logs')
            ->where('user_id', $user->id)
            ->where('sent_date', $today)
            ->count();

        if ($count >= self::DAILY_EMAIL_LIMIT) {
            Log::warning('User exceeded daily email limit', [
                'user_id' => $user->id,
                'email' => $user->email,
                'current_count' => $count,
                'limit' => self::DAILY_EMAIL_LIMIT,
                'notification_type' => $notificationType,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Log a sent email notification
     */
    public function logEmailSent(User $user, string $notificationType, array $notificationData = []): void
    {
        DB::table('email_notification_logs')->insert([
            'user_id' => $user->id,
            'email' => $user->email,
            'notification_type' => $notificationType,
            'notification_data' => json_encode($notificationData),
            'sent_date' => Carbon::today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Email notification logged', [
            'user_id' => $user->id,
            'email' => $user->email,
            'notification_type' => $notificationType,
        ]);
    }

    /**
     * Get the remaining email count for today
     */
    public function getRemainingEmailCount(User $user): int
    {
        $today = Carbon::today()->toDateString();

        $count = DB::table('email_notification_logs')
            ->where('user_id', $user->id)
            ->where('sent_date', $today)
            ->count();

        return max(0, self::DAILY_EMAIL_LIMIT - $count);
    }

    /**
     * Get today's email count for a user
     */
    public function getTodayEmailCount(User $user): int
    {
        $today = Carbon::today()->toDateString();

        return DB::table('email_notification_logs')
            ->where('user_id', $user->id)
            ->where('sent_date', $today)
            ->count();
    }

    /**
     * Get the daily email limit
     */
    public function getDailyLimit(): int
    {
        return self::DAILY_EMAIL_LIMIT;
    }

    /**
     * Check if user is approaching the limit (e.g., 80% of limit)
     */
    public function isApproachingLimit(User $user): bool
    {
        $currentCount = $this->getTodayEmailCount($user);
        $threshold = self::DAILY_EMAIL_LIMIT * 0.8;

        return $currentCount >= $threshold;
    }

    /**
     * Clean up old email logs (optional, for maintenance)
     */
    public function cleanupOldLogs(int $daysToKeep = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep)->toDateString();

        return DB::table('email_notification_logs')
            ->where('sent_date', '<', $cutoffDate)
            ->delete();
    }
}
