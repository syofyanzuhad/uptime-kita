<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramRateLimitService;

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
        $mappedChannels = collect($channels)->map(function ($channel) {
            $mapped = match ($channel) {
                'telegram' => 'telegram',
                'email'    => 'mail',
                'slack'    => 'slack',
                'sms'      => 'nexmo', // atau vonage tergantung setup
                default    => null
            };

            return $mapped;
        })->filter()->unique()->values()->all();

        return $mappedChannels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
        ->subject("Website Status: {$this->data['status']}")
        ->greeting("Halo, {$notifiable->name}")
        ->line("Website berikut mengalami perubahan status:")
        ->line("🔗 URL: {$this->data['url']}")
        ->line("⚠️ Status: {$this->data['status']}")
        ->action('Lihat Detail', url('/monitors/' . $this->data['id']))
        ->action('Uptime Kita', url('/'))
        ->salutation('Terima kasih,');
    }

    public function toTelegram($notifiable)
    {
        // Ambil channel Telegram user
        $telegramChannel = $notifiable->notificationChannels()
            ->where('type', 'telegram')
            ->where('is_enabled', true)
            ->first();

        if (!$telegramChannel) {
            return;
        }

        // Use the rate limiting service
        $rateLimitService = app(TelegramRateLimitService::class);

        // Check if we should send the notification
        if (!$rateLimitService->shouldSendNotification($notifiable, $telegramChannel)) {
            Log::info('Telegram notification rate limited', [
                'user_id' => $notifiable->id,
                'telegram_destination' => $telegramChannel->destination,
                'monitor_id' => $this->data['id'] ?? null,
                'status' => $this->data['status'] ?? null,
            ]);
            return;
        }

        try {
            $message = TelegramMessage::create()
                ->to($telegramChannel->destination)
                ->content("🔴 *Website DOWN*\n\nURL: `{$this->data['url']}`\nStatus: *{$this->data['status']}*")
                ->options(['parse_mode' => 'Markdown']);

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
            //
        ];
    }
}
