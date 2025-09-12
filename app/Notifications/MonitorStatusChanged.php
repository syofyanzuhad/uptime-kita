<?php

namespace App\Notifications;

use App\Services\EmailRateLimitService;
use App\Services\TelegramRateLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramMessage;

class MonitorStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $data)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Ambil semua channel yang aktif dari database
        $channels = $notifiable->notificationChannels()
            ->where('is_enabled', true)
            ->pluck('type')
            ->toArray();

        // Sesuaikan nama channel database dengan nama channel Laravel Notification
        return collect($channels)->map(function ($channel) use ($notifiable) {
            // Check email rate limit before adding mail channel
            if ($channel === 'email') {
                $emailRateLimitService = app(EmailRateLimitService::class);
                if (! $emailRateLimitService->canSendEmail($notifiable, 'monitor_status_changed')) {
                    return null; // Skip email channel if rate limited
                }
            }

            return match ($channel) {
                'telegram' => $notifiable->notificationChannels()->where('type', 'telegram')->where('is_enabled', true)->exists()
                    ? 'telegram' : null,
                'email' => 'mail',
                'slack' => 'slack',
                'sms' => 'nexmo',
                default => null,
            };
        })->filter()->unique()->values()->all();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Check email rate limit
        $emailRateLimitService = app(EmailRateLimitService::class);

        if (! $emailRateLimitService->canSendEmail($notifiable, 'monitor_status_changed')) {
            Log::warning('Email notification rate limited', [
                'user_id' => $notifiable->id,
                'email' => $notifiable->email,
                'notification_type' => 'monitor_status_changed',
                'monitor_id' => $this->data['id'] ?? null,
                'status' => $this->data['status'] ?? null,
                'remaining_emails' => $emailRateLimitService->getRemainingEmailCount($notifiable),
            ]);

            // Return null to skip sending the email
            return null;
        }

        // Log the email being sent
        $emailRateLimitService->logEmailSent($notifiable, 'monitor_status_changed', [
            'monitor_id' => $this->data['id'] ?? null,
            'url' => $this->data['url'] ?? null,
            'status' => $this->data['status'] ?? null,
        ]);

        // Check if user is approaching limit and add a warning
        $message = (new MailMessage)
            ->subject("Website Status: {$this->data['status']}")
            ->greeting("Halo, {$notifiable->name}")
            ->line('Website berikut mengalami perubahan status:')
            ->line("🔗 URL: {$this->data['url']}")
            ->line("⚠️ Status: {$this->data['status']}");

        // Add warning if approaching daily limit
        if ($emailRateLimitService->isApproachingLimit($notifiable)) {
            $remaining = $emailRateLimitService->getRemainingEmailCount($notifiable);
            $message->line('')
                ->line("⚠️ Perhatian: Anda memiliki {$remaining} email notifikasi tersisa untuk hari ini (batas: {$emailRateLimitService->getDailyLimit()} per hari).");
        }

        $message->action('Lihat Detail', url('/monitors/'.$this->data['id']))
            ->line('Kunjungi [Uptime Kita]('.url('/').') untuk informasi lebih lanjut.')
            ->salutation('Terima kasih,');

        return $message;
    }

    public function toTelegram($notifiable)
    {
        // Ambil channel Telegram user
        $telegramChannel = $notifiable->notificationChannels()
            ->where('type', 'telegram')
            ->where('is_enabled', true)
            ->first();

        if (! $telegramChannel) {
            return;
        }

        // Use the rate limiting service
        $rateLimitService = app(TelegramRateLimitService::class);

        // Check if we should send the notification
        if (! $rateLimitService->shouldSendNotification($notifiable, $telegramChannel)) {
            Log::info('Telegram notification rate limited', [
                'user_id' => $notifiable->id,
                'telegram_destination' => $telegramChannel->destination,
                'monitor_id' => $this->data['id'] ?? null,
                'status' => $this->data['status'] ?? null,
            ]);

            return;
        }

        try {
            $statusEmoji = $this->data['status'] === 'DOWN' ? '🔴' : '🟢';
            $statusText = $this->data['status'] === 'DOWN' ? 'Website DOWN' : 'Website UP';

            // if monitor is public, use public url
            if (@$this->data['is_public']) {
                $monitorUrl = config('app.url').'/m/'.$this->data['url'];
            } else {
                $monitorUrl = config('app.url').'/monitor/'.$this->data['id'];
            }

            $message = TelegramMessage::create()
                ->to($telegramChannel->destination)
                ->content("{$statusEmoji} *{$statusText}*\n\nURL: `{$this->data['url']}`\nStatus: *{$this->data['status']}*")
                ->options(['parse_mode' => 'Markdown'])
                ->button('View Monitor', $monitorUrl)
                ->button('Open Website', $this->data['url']);

            // Track successful notification
            $rateLimitService->trackSuccessfulNotification($notifiable, $telegramChannel);

            return $message;
        } catch (\Exception $e) {
            // If we get a 429 error, track it for backoff
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'Too Many Requests')) {
                $rateLimitService->trackFailedNotification($notifiable, $telegramChannel);

                Log::error('Telegram notification failed with 429 error', [
                    'user_id' => $notifiable->id,
                    'telegram_destination' => $telegramChannel->destination,
                    'error' => $e->getMessage(),
                ]);
            } else {
                Log::error('Telegram notification failed', [
                    'user_id' => $notifiable->id,
                    'telegram_destination' => $telegramChannel->destination,
                    'error' => $e->getMessage(),
                ]);
            }

            // Re-throw the exception so Laravel can handle it
            throw $e;
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->data['id'],
            'url' => $this->data['url'],
            'status' => $this->data['status'],
            'message' => $this->data['message'],
        ];
    }
}
