<?php

namespace App\Services;

use App\Helpers\UptimeHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailRateLimitService
{
    /**
     * Check if the user can send an email notification
     */
    public function canSendEmail(User $user, string $notificationType): bool
    {
        // Get the daily limit based on deployment type and user plan
        $dailyLimit = $this->getDailyLimitForUser($user);

        // If limit is 0 (unlimited), always allow
        if ($dailyLimit === 0) {
            return true;
        }

        $today = Carbon::today()->toDateString();

        $count = DB::table('email_notification_logs')
            ->where('user_id', $user->id)
            ->where('sent_date', $today)
            ->count();

        if ($count >= $dailyLimit) {
            Log::warning('User exceeded daily email limit', [
                'user_id' => $user->id,
                'email' => $user->email,
                'current_count' => $count,
                'limit' => $dailyLimit,
                'notification_type' => $notificationType,
                'deployment_type' => UptimeHelper::getDeploymentType(),
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
        $dailyLimit = $this->getDailyLimitForUser($user);

        // If unlimited, return a large number or -1 to indicate unlimited
        if ($dailyLimit === 0) {
            return -1; // -1 indicates unlimited
        }

        $today = Carbon::today()->toDateString();

        $count = DB::table('email_notification_logs')
            ->where('user_id', $user->id)
            ->where('sent_date', $today)
            ->count();

        return max(0, $dailyLimit - $count);
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
     * Get the daily email limit for display
     */
    public function getDailyLimit(): int
    {
        return UptimeHelper::getEmailDailyLimit();
    }

    /**
     * Get the daily limit for a specific user based on deployment type and plan
     */
    public function getDailyLimitForUser(User $user): int
    {
        if (UptimeHelper::isSelfHosted()) {
            return UptimeHelper::getEmailDailyLimit();
        }

        // For SaaS, get limit based on user's plan
        return UptimeHelper::getUserPlan($user, 'email_daily_limit');
    }

    /**
     * Check if user is approaching the limit (e.g., 80% of limit)
     */
    public function isApproachingLimit(User $user): bool
    {
        $dailyLimit = $this->getDailyLimitForUser($user);

        // If unlimited, never approaching limit
        if ($dailyLimit === 0) {
            return false;
        }

        $currentCount = $this->getTodayEmailCount($user);
        $threshold = $dailyLimit * 0.8;

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
