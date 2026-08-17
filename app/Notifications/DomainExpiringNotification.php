<?php

namespace App\Notifications;

use App\Models\Monitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramMessage;

class DomainExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(public Monitor $monitor, public int $daysLeft) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
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
            } elseif ($channel->type === 'slack') {
                $via[] = 'slack';
            }
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $host = $this->monitor->host;

        $message = (new MailMessage)
            ->subject($this->expired() ? "⚠️ Domain {$host} Telah Kedaluwarsa" : "⚠️ Domain {$host} Akan Kedaluwarsa")
            ->greeting("Halo, {$notifiable->name}")
            ->line('Kami ingin mengingatkan Anda tentang pendaftaran domain berikut:')
            ->line("🔗 Domain: **{$host}**")
            ->line($this->statusLine())
            ->line("📅 Tanggal kedaluwarsa: **{$this->monitor->domain_expiration_date->format('d M Y')}**");

        $message->action('Lihat Detail Monitor', url('/monitors/'.$this->monitor->id))
            ->line('Kunjungi [Uptime Kita]('.url('/').') untuk informasi lebih lanjut.')
            ->salutation('Terima kasih,');

        return $message;
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): TelegramMessage
    {
        $host = $this->monitor->host;
        $expiryDate = $this->monitor->domain_expiration_date->format('d M Y');
        $emoji = $this->expired() ? '🔴' : '⏰';

        $content = "{$emoji} *Peringatan Kedaluwarsa Domain*\n\n"
            ."Domain: `{$host}`\n"
            ."{$this->statusLine()}\n"
            ."Tanggal kedaluwarsa: *{$expiryDate}*";

        $monitorUrl = $this->monitor->is_public
            ? config('app.url').'/m/'.$host
            : config('app.url').'/monitor/'.$this->monitor->id;

        return TelegramMessage::create()
            ->content($content)
            ->options(['parse_mode' => 'Markdown'])
            ->button('Lihat Monitor', $monitorUrl);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'monitor_id' => $this->monitor->id,
            'url' => $this->monitor->url ? (string) $this->monitor->url : null,
            'days_left' => $this->daysLeft,
            'domain_expiration_date' => $this->monitor->domain_expiration_date?->toDateTimeString(),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Domain expiration notification failed', [
            'monitor_id' => $this->monitor->id,
            'exception' => $exception->getMessage(),
        ]);
    }

    protected function expired(): bool
    {
        return $this->daysLeft < 0;
    }

    protected function statusLine(): string
    {
        if ($this->expired()) {
            return '⚠️ Status: Domain ini **telah kedaluwarsa** dan perlu segera diperpanjang!';
        }

        if ($this->daysLeft === 0) {
            return '⚠️ Status: Domain ini **kedaluwarsa hari ini** dan perlu segera diperpanjang!';
        }

        return "⚠️ Status: Domain ini akan kedaluwarsa dalam **{$this->daysLeft} hari**.";
    }
}
