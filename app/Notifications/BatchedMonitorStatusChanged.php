<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramMessage;

class BatchedMonitorStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $backoff = 60;

    /**
     * Create a new notification instance.
     *
     * @param  array  $events  List of monitor status change events
     */
    public function __construct(public array $events)
    {
        //
    }

    public function via(object $notifiable): array
    {
        // Use eager-loaded channels if available to prevent N+1
        $channels = $notifiable->relationLoaded('notificationChannels')
            ? $notifiable->notificationChannels->where('is_enabled', true)
            : $notifiable->notificationChannels()->where('is_enabled', true)->get();

        $via = [];

        foreach ($channels as $channel) {
            if ($channel->type === 'email') {
                $via[] = 'mail';
            } elseif ($channel->type === 'telegram') {
                if (! blank($channel->destination) && preg_match('/^-?\d+$/', (string) $channel->destination)) {
                    $via[] = 'telegram';
                }
            }
        }

        return $via;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->events);
        $subject = "Alert: {$count} Monitor Status Changes";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo, {$notifiable->name}")
            ->line("There have been {$count} monitor status changes since the last update:");

        foreach ($this->events as $event) {
            $host = parse_url($event['url'], PHP_URL_HOST) ?? $event['url'];
            $emoji = $event['status'] === 'DOWN' ? '🔴' : '🟢';
            $message->line("{$emoji} **{$host}** is now **{$event['status']}**");
        }

        $message->action('View Wallboard', url('/monitors'))
            ->salutation('Terima kasih,');

        return $message;
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $count = count($this->events);
        $statusEmoji = '⚠️';

        $content = "{$statusEmoji} *Monitor Alert Summary*\n";
        $content .= "Detected {$count} changes:\n\n";

        foreach ($this->events as $event) {
            $host = parse_url($event['url'], PHP_URL_HOST) ?? $event['url'];
            $emoji = $event['status'] === 'DOWN' ? '🔴' : '🟢';
            $content .= "{$emoji} `{$host}`: *{$event['status']}*\n";
        }

        return TelegramMessage::create()
            ->content($content)
            ->options(['parse_mode' => 'Markdown'])
            ->button('Open Wallboard', url('/monitors'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Batched notification failed', ['exception' => $exception->getMessage()]);
    }
}
