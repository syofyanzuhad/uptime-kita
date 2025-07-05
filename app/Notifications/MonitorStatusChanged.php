<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
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

        return TelegramMessage::create()
            ->to($telegramChannel->destination)
            ->content("🔴 *Website DOWN*\n\nURL: `{$this->data['url']}`\nStatus: *{$this->data['status']}*")
            ->options(['parse_mode' => 'Markdown']);
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
