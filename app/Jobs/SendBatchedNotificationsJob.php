<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\BatchedMonitorStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendBatchedNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheKey = 'pending_monitor_notifications';

        // Atomically pull and clear the pending notifications
        $pendingEvents = Cache::pull($cacheKey, []);

        if (empty($pendingEvents)) {
            return;
        }

        Log::info('Processing batched notifications', ['count' => count($pendingEvents)]);

        // Group events by user_id
        $userEvents = [];
        foreach ($pendingEvents as $event) {
            foreach ($event['user_ids'] as $userId) {
                $userEvents[$userId][] = [
                    'monitor_id' => $event['monitor_id'],
                    'url' => $event['url'],
                    'status' => $event['status'],
                ];
            }
        }

        // Fetch all users in one query with their enabled channels to avoid N+1
        $userIds = array_keys($userEvents);
        $users = User::with(['notificationChannels' => function ($query) {
            $query->where('is_enabled', true);
        }])->whereIn('id', $userIds)->get()->keyBy('id');

        // Send batched notifications to each user
        foreach ($userEvents as $userId => $events) {
            $user = $users->get($userId);
            if ($user) {
                try {
                    // Note: BatchedMonitorStatusChanged uses $notifiable->notificationChannels()
                    // which is now eager-loaded.
                    Notification::send($user, new BatchedMonitorStatusChanged($events));
                    Log::debug("Sent batched notification to user {$userId}", ['event_count' => count($events)]);
                } catch (\Exception $e) {
                    Log::error("Failed to send batched notification to user {$userId}", ['error' => $e->getMessage()]);
                }
            } else {
                Log::warning('SendBatchedNotificationsJob: User not found', ['user_id' => $userId]);
            }
        }
    }
}
