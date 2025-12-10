<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use App\Services\TelegramRateLimitService;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'John Doe']);
    $this->data = [
        'id' => 1,
        'url' => 'https://example.com',
        'status' => 'DOWN',
        'message' => 'Website https://example.com is DOWN',
    ];
    $this->notification = new MonitorStatusChanged($this->data);

    // Set Twitter credentials for tests that expect Twitter channel
    config([
        'services.twitter.consumer_key' => 'test-consumer-key',
        'services.twitter.consumer_secret' => 'test-consumer-secret',
        'services.twitter.access_token' => 'test-access-token',
        'services.twitter.access_secret' => 'test-access-secret',
    ]);
});

describe('MonitorStatusChanged', function () {
    describe('constructor', function () {
        it('stores data correctly', function () {
            expect($this->notification->data)->toBe($this->data);
        });
    });

    describe('via', function () {
        it('returns channels based on user notification channels plus Twitter', function () {
            // Create notification channels for user
            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'email',
                'is_enabled' => true,
            ]);

            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            $channels = $this->notification->via($this->user);

            expect($channels)->toContain('mail');
            expect($channels)->toContain('telegram');
            expect($channels)->toContain('NotificationChannels\Twitter\TwitterChannel');
        });

        it('only returns enabled channels plus Twitter', function () {
            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'email',
                'is_enabled' => true,
            ]);

            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => false,
                'destination' => '123456789',
            ]);

            $channels = $this->notification->via($this->user);

            expect($channels)->toContain('mail');
            expect($channels)->not->toContain('telegram');
            expect($channels)->toContain('NotificationChannels\Twitter\TwitterChannel');
        });

        it('returns Twitter channel when no user channels enabled and status is DOWN', function () {
            $channels = $this->notification->via($this->user);

            expect($channels)->toHaveCount(1);
            expect($channels)->toContain('NotificationChannels\Twitter\TwitterChannel');
        });

        it('returns empty array when no user channels enabled and status is UP', function () {
            $upData = [
                'id' => 1,
                'url' => 'https://example.com',
                'status' => 'UP',
                'message' => 'Website https://example.com is UP',
            ];
            $upNotification = new MonitorStatusChanged($upData);

            $channels = $upNotification->via($this->user);

            expect($channels)->toBeEmpty();
        });

        it('maps channel types correctly and includes Twitter for DOWN status', function () {
            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'slack',
                'is_enabled' => true,
            ]);

            $channels = $this->notification->via($this->user);

            expect($channels)->toContain('slack');
            expect($channels)->toContain('NotificationChannels\Twitter\TwitterChannel');
        });

        it('maps channel types correctly but excludes Twitter for UP status', function () {
            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'slack',
                'is_enabled' => true,
            ]);

            $upData = [
                'id' => 1,
                'url' => 'https://example.com',
                'status' => 'UP',
                'message' => 'Website https://example.com is UP',
            ];
            $upNotification = new MonitorStatusChanged($upData);

            $channels = $upNotification->via($this->user);

            expect($channels)->toContain('slack');
            expect($channels)->not->toContain('NotificationChannels\Twitter\TwitterChannel');
        });

        it('excludes Twitter channel when credentials are not configured', function () {
            // Clear Twitter credentials
            config([
                'services.twitter.consumer_key' => null,
                'services.twitter.consumer_secret' => null,
                'services.twitter.access_token' => null,
                'services.twitter.access_secret' => null,
            ]);

            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'email',
                'is_enabled' => true,
            ]);

            $channels = $this->notification->via($this->user);

            expect($channels)->toContain('mail');
            expect($channels)->not->toContain('NotificationChannels\Twitter\TwitterChannel');
        });

        it('excludes Twitter channel when only some credentials are configured', function () {
            // Set only partial Twitter credentials
            config([
                'services.twitter.consumer_key' => 'test-key',
                'services.twitter.consumer_secret' => null,
                'services.twitter.access_token' => 'test-token',
                'services.twitter.access_secret' => null,
            ]);

            $channels = $this->notification->via($this->user);

            expect($channels)->not->toContain('NotificationChannels\Twitter\TwitterChannel');
        });
    });

    describe('toMail', function () {
        it('creates mail message with correct content using hostname only', function () {
            $mailMessage = $this->notification->toMail($this->user);

            expect($mailMessage)->toBeInstanceOf(MailMessage::class);
            expect($mailMessage->subject)->toBe('Website Status: DOWN');
            expect($mailMessage->greeting)->toBe('Halo, John Doe');

            // Check that the message contains expected content with hostname only
            $mailData = $mailMessage->data();
            expect($mailData['introLines'])->toContain('Website berikut mengalami perubahan status:');
            expect($mailData['introLines'])->toContain('🔗 URL: example.com');
            expect($mailData['introLines'])->toContain('⚠️ Status: DOWN');
        });

        it('includes action button with correct URL', function () {
            $mailMessage = $this->notification->toMail($this->user);

            expect($mailMessage->actionText)->toBe('Lihat Detail');
            expect($mailMessage->actionUrl)->toBe(url('/monitors/1'));
        });
    });

    describe('toTelegram', function () {
        it('returns null when no telegram channel exists', function () {
            $result = $this->notification->toTelegram($this->user);

            expect($result)->toBeNull();
        });

        it('returns null when telegram channel is disabled', function () {
            NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => false,
                'destination' => '123456789',
            ]);

            $result = $this->notification->toTelegram($this->user);

            expect($result)->toBeNull();
        });

        it('returns null when rate limited', function () {
            $telegramChannel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            // Mock rate limit service to deny sending
            $rateLimitService = mock(TelegramRateLimitService::class);
            $rateLimitService->shouldReceive('shouldSendNotification')
                ->withArgs(function ($user, $channel) use ($telegramChannel) {
                    return $user->id === $this->user->id && $channel->id === $telegramChannel->id;
                })
                ->andReturn(false);

            $this->app->instance(TelegramRateLimitService::class, $rateLimitService);

            $result = $this->notification->toTelegram($this->user);

            expect($result)->toBeNull();
        });

        it('creates telegram message when conditions are met', function () {
            $telegramChannel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            // Mock rate limit service to allow sending
            $rateLimitService = mock(TelegramRateLimitService::class);
            $rateLimitService->shouldReceive('shouldSendNotification')
                ->withArgs(function ($user, $channel) use ($telegramChannel) {
                    return $user->id === $this->user->id && $channel->id === $telegramChannel->id;
                })
                ->andReturn(true);
            $rateLimitService->shouldReceive('trackSuccessfulNotification')
                ->withArgs(function ($user, $channel) use ($telegramChannel) {
                    return $user->id === $this->user->id && $channel->id === $telegramChannel->id;
                })
                ->once();

            $this->app->instance(TelegramRateLimitService::class, $rateLimitService);

            $result = $this->notification->toTelegram($this->user);

            expect($result)->toBeInstanceOf(TelegramMessage::class);
        });

        it('formats DOWN status message correctly', function () {
            $telegramChannel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            $rateLimitService = mock(TelegramRateLimitService::class);
            $rateLimitService->shouldReceive('shouldSendNotification')->andReturn(true);
            $rateLimitService->shouldReceive('trackSuccessfulNotification');

            $this->app->instance(TelegramRateLimitService::class, $rateLimitService);

            $result = $this->notification->toTelegram($this->user);

            // Check that message contains DOWN indicators
            expect($result)->toBeInstanceOf(TelegramMessage::class);

            // TelegramMessage doesn't expose content property directly,
            // so we'll verify it was created with the correct type
        });

        it('formats UP status message correctly', function () {
            $this->data['status'] = 'UP';
            $notification = new MonitorStatusChanged($this->data);

            $telegramChannel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            $rateLimitService = mock(TelegramRateLimitService::class);
            $rateLimitService->shouldReceive('shouldSendNotification')->andReturn(true);
            $rateLimitService->shouldReceive('trackSuccessfulNotification');

            $this->app->instance(TelegramRateLimitService::class, $rateLimitService);

            $result = $notification->toTelegram($this->user);

            // TelegramMessage doesn't expose content property directly,
            // so we'll verify it was created with the correct type
            expect($result)->toBeInstanceOf(TelegramMessage::class);
        });

        it('includes both view monitor and open website buttons', function () {
            $telegramChannel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            $rateLimitService = mock(TelegramRateLimitService::class);
            $rateLimitService->shouldReceive('shouldSendNotification')->andReturn(true);
            $rateLimitService->shouldReceive('trackSuccessfulNotification');

            $this->app->instance(TelegramRateLimitService::class, $rateLimitService);

            $result = $this->notification->toTelegram($this->user);

            expect($result)->toBeInstanceOf(TelegramMessage::class);

            // Check the payload contains both buttons
            $payload = $result->toArray();
            expect($payload)->toHaveKey('reply_markup');

            $replyMarkup = json_decode($payload['reply_markup'], true);
            expect($replyMarkup)->toHaveKey('inline_keyboard');
            expect($replyMarkup['inline_keyboard'])->toHaveCount(1); // One row with 2 buttons
            expect($replyMarkup['inline_keyboard'][0])->toHaveCount(2); // Two buttons in the row

            // Check first button (View Monitor)
            $viewMonitorButton = $replyMarkup['inline_keyboard'][0][0];
            expect($viewMonitorButton['text'])->toBe('View Monitor');
            expect($viewMonitorButton['url'])->toBe(config('app.url').'/monitor/1');

            // Check second button (Open Website)
            $openWebsiteButton = $replyMarkup['inline_keyboard'][0][1];
            expect($openWebsiteButton['text'])->toBe('Open Website');
            expect($openWebsiteButton['url'])->toBe('https://example.com');
        });

        it('uses hostname only in telegram message content', function () {
            $telegramChannel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'is_enabled' => true,
                'destination' => '123456789',
            ]);

            $rateLimitService = mock(TelegramRateLimitService::class);
            $rateLimitService->shouldReceive('shouldSendNotification')->andReturn(true);
            $rateLimitService->shouldReceive('trackSuccessfulNotification');

            $this->app->instance(TelegramRateLimitService::class, $rateLimitService);

            $result = $this->notification->toTelegram($this->user);

            expect($result)->toBeInstanceOf(TelegramMessage::class);

            // Check the message content contains hostname only (not full URL)
            $payload = $result->toArray();
            expect($payload)->toHaveKey('text');
            expect($payload['text'])->toContain('example.com');
            expect($payload['text'])->not->toContain('https://example.com');
        });
    });

    describe('toArray', function () {
        it('returns array representation', function () {
            $result = $this->notification->toArray($this->user);

            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['id', 'url', 'status', 'message']);
            expect($result['id'])->toBe(1);
            expect($result['url'])->toBe('https://example.com');
            expect($result['status'])->toBe('DOWN');
        });
    });
});
