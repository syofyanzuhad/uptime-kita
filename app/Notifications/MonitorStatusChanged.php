<?php

namespace App\Notifications;

use App\Services\EmailRateLimitService;
use App\Services\TelegramRateLimitService;
use App\Services\TwitterRateLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Twitter\TwitterChannel;
use NotificationChannels\Twitter\TwitterStatusUpdate;

class MonitorStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The notifiable instance.
     *
     * @var object|null
     */
    public ?object $notifiable = null;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $data)
    {
        //
    }

    /**
     * Check if Twitter credentials are configured.
     */
    protected function isTwitterConfigured(): bool
    {
        return ! empty(config('services.twitter.consumer_key'))
            && ! empty(config('services.twitter.consumer_secret'))
            && ! empty(config('services.twitter.access_token'))
            && ! empty(config('services.twitter.access_secret'));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $this->notifiable = $notifiable;

        // Get all active channels from database
        $channels = $notifiable->notificationChannels()
            ->where('is_enabled', true)
            ->get();

        $allChannels = collect();

        foreach ($channels as $channel) {
            if ($channel->type === 'email') {
                $emailRateLimitService = app(EmailRateLimitService::class);
                if ($emailRateLimitService->canSendEmail($notifiable, 'monitor_status_changed')) {
                    $allChannels->push('mail');
                }

                continue;
            }

            if ($channel->type === 'telegram') {
                // Short-circuit if destination is clearly invalid
                if (blank($channel->destination) || ! preg_match('/^-?\d+$/', (string) $channel->destination)) {
                    Log::warning('MonitorStatusChanged: Skipping Telegram channel with invalid destination', [
                        'user_id' => $notifiable->id,
                        'channel_id' => $channel->id,
                        'destination' => $channel->destination,
                    ]);

                    continue;
                }

                $telegramRateLimitService = app(TelegramRateLimitService::class);
                if ($telegramRateLimitService->shouldSendNotification($notifiable, $channel)) {
                    $allChannels->push('telegram');
                }

                continue;
            }

            $mappedChannel = match ($channel->type) {
                'slack' => 'slack',
                'sms' => 'nexmo',
                default => null,
            };

            if ($mappedChannel) {
                $allChannels->push($mappedChannel);
            }
        }

        // Add Twitter as system notification only for DOWN events if configured and not rate limited
        if ($this->data['status'] === 'DOWN') {
            if (! $this->isTwitterConfigured()) {
                Log::debug('Twitter channel skipped: credentials not configured');
            } else {
                $twitterRateLimitService = app(TwitterRateLimitService::class);
                if ($twitterRateLimitService->shouldSendNotification($notifiable, null)) {
                    $allChannels = $allChannels->push(TwitterChannel::class);
                } else {
                    Log::info('Twitter channel excluded due to rate limit', [
                        'user_id' => $notifiable->id,
                        'monitor_status' => $this->data['status'] ?? null,
                    ]);
                }
            }
        }

        return $allChannels->unique()->values()->all();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Extract hostname from URL
        $parsedUrl = parse_url($this->data['url']);
        $host = $parsedUrl['host'] ?? $this->data['url'];

        $emailRateLimitService = app(EmailRateLimitService::class);
        $emailRateLimitService->logEmailSent($notifiable, 'monitor_status_changed', [
            'monitor_id' => $this->data['id'] ?? null,
            'url' => $host,
            'status' => $this->data['status'] ?? null,
        ]);

        $message = (new MailMessage)
            ->subject("Website Status: {$this->data['status']}")
            ->greeting("Halo, {$notifiable->name}")
            ->line('Website berikut mengalami perubahan status:')
            ->line("🔗 URL: {$host}")
            ->line("⚠️ Status: {$this->data['status']}");

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

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $telegramChannel = $notifiable->notificationChannels()
            ->where('type', 'telegram')
            ->where('is_enabled', true)
            ->firstOrFail();

        $rateLimitService = app(TelegramRateLimitService::class);

        $statusEmoji = $this->data['status'] === 'DOWN' ? '🔴' : '🟢';
        $statusText = $this->data['status'] === 'DOWN' ? 'Website DOWN' : 'Website UP';

        // Extract hostname from URL
        $parsedUrl = parse_url($this->data['url']);
        $host = $parsedUrl['host'] ?? $this->data['url'];

        if (@$this->data['is_public']) {
            $monitorUrl = config('app.url').'/m/'.$host;
        } else {
            $monitorUrl = config('app.url').'/monitor/'.$this->data['id'];
        }

        $message = TelegramMessage::create()
            ->to($telegramChannel->destination)
            ->content("{$statusEmoji} *{$statusText}*\n\nURL: `{$host}`\nStatus: *{$this->data['status']}*")
            ->options(['parse_mode' => 'Markdown'])
            ->button('View Monitor', $monitorUrl)
            ->button('Open Website', $this->data['url']);

        $rateLimitService->trackSuccessfulNotification($notifiable, $telegramChannel);

        return $message;
    }

    public function toTwitter($notifiable)
    {
        try {
            // Check if Twitter credentials are configured
            if (! $this->isTwitterConfigured()) {
                Log::debug('Twitter notification skipped: credentials not configured');

                return new TwitterStatusUpdate('');
            }

            // check if monitor is public
            if (! @$this->data['is_public']) {
                return null;
            }

            // Use the rate limiting service
            $rateLimitService = app(TwitterRateLimitService::class);

            // Double-check rate limit at the time of sending
            // This handles race conditions between via() and toTwitter() calls
            if (! $rateLimitService->shouldSendNotification($notifiable, null)) {
                Log::info('Twitter notification rate limited in toTwitter method', [
                    'user_id' => $notifiable->id,
                    'monitor_id' => $this->data['id'] ?? null,
                    'status' => $this->data['status'] ?? null,
                ]);

                // Return empty tweet instead of null to avoid TypeError
                // The Twitter API will reject empty tweets, effectively skipping the notification
                return new TwitterStatusUpdate('');
            }

            $statusEmoji = $this->data['status'] === 'DOWN' ? '🔴' : '🟢';
            $statusText = $this->data['status'] === 'DOWN' ? 'DOWN' : 'UP';
            $parsedUrl = parse_url($this->data['url']);
            $host = $parsedUrl['host'];

            // Create tweet content
            $tweetContent = "{$statusEmoji} Monitor Alert: {$host} is {$statusText}\n\n";

            // Add timestamp
            $tweetContent .= 'Time: '.now()->format('Y-m-d H:i:s')." UTC\n";

            // Add hashtags
            $tweetContent .= '#UptimeKita #UptimeMonitoring #WebsiteStatus';

            // If monitor is public, add link
            if (@$this->data['is_public']) {
                $monitorUrl = config('app.url').'/m/'.$host;
                $tweetContent .= "\n\nDetails: {$monitorUrl}";
            }

            // Track successful notification
            $rateLimitService->trackSuccessfulNotification($notifiable, null);

            return new TwitterStatusUpdate($tweetContent);
        } catch (\Exception $e) {
            // If we get a 429 error, track it for backoff
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'Too Many Requests')) {
                $rateLimitService = app(TwitterRateLimitService::class);
                $rateLimitService->trackFailedNotification($notifiable, null);

                Log::error('Twitter notification failed with 429 error - setting backoff', [
                    'user_id' => $notifiable->id,
                    'error' => $e->getMessage(),
                ]);

                // We don't re-throw for 429 errors because we handle them with our own rate limiting/backoff.
                // This prevents "attempted too many times" errors in the queue.
                return new TwitterStatusUpdate('');
            }

            Log::error('Twitter notification failed', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Monitor notification failed permanently', [
            'monitor_id' => $this->data['id'] ?? null,
            'exception' => $exception->getMessage(),
        ]);

        if (str_contains($exception->getMessage(), 'chat not found') && $this->notifiable) {
            if (method_exists($this->notifiable, 'notificationChannels')) {
                $this->notifiable->notificationChannels()
                    ->where('type', 'telegram')
                    ->where('is_enabled', true)
                    ->update(['is_enabled' => false]);

                Log::warning('Disabled Telegram channels for notifiable due to permanent delivery failure (chat not found)', [
                    'notifiable_id' => $this->notifiable->id ?? null,
                    'monitor_id' => $this->data['id'] ?? null,
                ]);
            }
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
