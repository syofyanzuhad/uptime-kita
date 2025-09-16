<?php

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use App\Services\TwitterRateLimitService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Twitter\TwitterChannel;

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();
});

it('sends twitter notification when monitor goes down', function () {
    Notification::fake();

    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'uptime_status' => 'DOWN',
    ]);

    // Associate user with monitor
    $monitor->users()->attach($user->id, ['is_active' => true]);

    // Create Twitter notification channel for the user
    $twitterChannel = NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'twitter',
        'destination' => '@testuser',
        'is_enabled' => true,
    ]);

    $notificationData = [
        'id' => $monitor->id,
        'url' => $monitor->url,
        'status' => 'DOWN',
        'message' => 'Monitor is down',
        'is_public' => false,
    ];

    $user->notify(new MonitorStatusChanged($notificationData));

    Notification::assertSentTo(
        [$user],
        MonitorStatusChanged::class,
        function ($notification, $channels) {
            return in_array(TwitterChannel::class, $channels);
        }
    );
});

it('respects twitter rate limits', function () {
    $user = User::factory()->create();
    $channel = NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'twitter',
        'destination' => '@testuser',
        'is_enabled' => true,
    ]);

    $service = new TwitterRateLimitService;

    // Initially should be able to send
    expect($service->shouldSendNotification($user, $channel))->toBeTrue();

    // Simulate sending 30 notifications (hourly limit)
    for ($i = 0; $i < 30; $i++) {
        $service->trackSuccessfulNotification($user, $channel);
    }

    // Should not be able to send after hitting hourly limit
    expect($service->shouldSendNotification($user, $channel))->toBeFalse();

    // Check remaining tweets
    $remaining = $service->getRemainingTweets($user, $channel);
    expect($remaining['hourly_remaining'])->toBe(0);
    expect($remaining['daily_remaining'])->toBe(170); // 200 - 30
});

it('tracks twitter notifications in cache', function () {
    $user = User::factory()->create();
    $channel = NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'twitter',
        'destination' => '@testuser',
        'is_enabled' => true,
    ]);

    $service = new TwitterRateLimitService;

    // Track a successful notification
    $service->trackSuccessfulNotification($user, $channel);

    // Check cache keys exist
    $hourlyKey = sprintf('twitter_rate_limit:hourly:%d:%d', $user->id, $channel->id);
    $dailyKey = sprintf('twitter_rate_limit:daily:%d:%d', $user->id, $channel->id);

    expect(Cache::has($hourlyKey))->toBeTrue();
    expect(Cache::has($dailyKey))->toBeTrue();
    expect(Cache::get($hourlyKey))->toBe(1);
    expect(Cache::get($dailyKey))->toBe(1);
});

it('always sends twitter notification for DOWN events regardless of channel settings', function () {
    Notification::fake();

    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'uptime_status' => 'DOWN',
    ]);

    // Associate user with monitor
    $monitor->users()->attach($user->id, ['is_active' => true]);

    // Create disabled Twitter notification channel
    $twitterChannel = NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'twitter',
        'destination' => '@testuser',
        'is_enabled' => false,
    ]);

    // Also create an enabled email channel so notification is still sent
    NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => true,
    ]);

    $notificationData = [
        'id' => $monitor->id,
        'url' => $monitor->url,
        'status' => 'DOWN',
        'message' => 'Monitor is down',
        'is_public' => false,
    ];

    $user->notify(new MonitorStatusChanged($notificationData));

    Notification::assertSentTo(
        [$user],
        MonitorStatusChanged::class,
        function ($notification, $channels) {
            // Twitter channel should always be included for DOWN events
            return in_array(TwitterChannel::class, $channels);
        }
    );
});

it('includes public monitor link in tweet when monitor is public', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'uptime_status' => 'DOWN',
        'is_public' => true,
    ]);

    // Associate user with monitor
    $monitor->users()->attach($user->id, ['is_active' => true]);

    $twitterChannel = NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'twitter',
        'destination' => '@testuser',
        'is_enabled' => true,
    ]);

    $notificationData = [
        'id' => $monitor->id,
        'url' => $monitor->url,
        'status' => 'DOWN',
        'message' => 'Monitor is down',
        'is_public' => true,
    ];

    $notification = new MonitorStatusChanged($notificationData);
    $twitterUpdate = $notification->toTwitter($user);

    expect($twitterUpdate)->not->toBeNull();
    expect($twitterUpdate->getContent())->toContain('Monitor Alert');
    expect($twitterUpdate->getContent())->toContain(parse_url($monitor->url, PHP_URL_HOST));
    expect($twitterUpdate->getContent())->toContain('#UptimeKita');
    expect($twitterUpdate->getContent())->toContain('Details:');
});

it('excludes twitter channel when rate limited', function () {
    Notification::fake();

    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'uptime_status' => 'DOWN',
    ]);

    // Associate user with monitor
    $monitor->users()->attach($user->id, ['is_active' => true]);

    // Create email channel to ensure notification still sends
    NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => true,
    ]);

    // Simulate hitting Twitter rate limit
    $service = new TwitterRateLimitService;
    for ($i = 0; $i < 30; $i++) {
        $service->trackSuccessfulNotification($user, null);
    }

    $notificationData = [
        'id' => $monitor->id,
        'url' => $monitor->url,
        'status' => 'DOWN',
        'message' => 'Monitor is down',
        'is_public' => false,
    ];

    $user->notify(new MonitorStatusChanged($notificationData));

    Notification::assertSentTo(
        [$user],
        MonitorStatusChanged::class,
        function ($notification, $channels) {
            // Twitter channel should NOT be included when rate limited
            return ! in_array(TwitterChannel::class, $channels) && in_array('mail', $channels);
        }
    );
});

it('returns null from toTwitter when rate limited', function () {
    $user = User::factory()->create();

    // Simulate hitting Twitter rate limit
    $service = new TwitterRateLimitService;
    for ($i = 0; $i < 30; $i++) {
        $service->trackSuccessfulNotification($user, null);
    }

    $notificationData = [
        'id' => 1,
        'url' => 'https://example.com',
        'status' => 'DOWN',
        'message' => 'Monitor is down',
        'is_public' => false,
    ];

    $notification = new MonitorStatusChanged($notificationData);
    $twitterUpdate = $notification->toTwitter($user);

    expect($twitterUpdate)->toBeNull();
});
