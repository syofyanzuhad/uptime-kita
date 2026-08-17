<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\Services\TwitterRateLimitService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create();
    $this->service = new TwitterRateLimitService;
});

it('allows sending when no notifications sent', function () {
    expect($this->service->shouldSendNotification($this->user))->toBeTrue();
});

it('blocks sending when hourly limit reached', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->service->trackSuccessfulNotification($this->user);
    }

    expect($this->service->shouldSendNotification($this->user))->toBeFalse();
});

it('blocks sending when daily limit reached', function () {
    for ($i = 0; $i < 200; $i++) {
        $this->service->trackSuccessfulNotification($this->user);
    }

    expect($this->service->shouldSendNotification($this->user))->toBeFalse();
});

it('blocks sending during backoff period', function () {
    $this->service->trackFailedNotification($this->user);

    expect($this->service->shouldSendNotification($this->user))->toBeFalse();
});

it('allows sending after backoff period expires', function () {
    $this->service->trackFailedNotification($this->user);

    // Backoff starts at 2 minutes; move past it
    $this->travel(3)->minutes();

    expect($this->service->shouldSendNotification($this->user))->toBeTrue();
});

it('resets backoff after successful notification', function () {
    $this->service->trackFailedNotification($this->user);
    expect($this->service->shouldSendNotification($this->user))->toBeFalse();

    $this->service->trackSuccessfulNotification($this->user);

    expect($this->service->shouldSendNotification($this->user))->toBeTrue();
});

it('tracks per-channel limits separately', function () {
    $channel = NotificationChannel::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'twitter',
    ]);

    for ($i = 0; $i < 30; $i++) {
        $this->service->trackSuccessfulNotification($this->user, $channel);
    }

    // System-level is separate from channel-level
    expect($this->service->shouldSendNotification($this->user))->toBeTrue();
    expect($this->service->shouldSendNotification($this->user, $channel))->toBeFalse();
});

it('reports remaining tweets', function () {
    $this->service->trackSuccessfulNotification($this->user);

    $remaining = $this->service->getRemainingTweets($this->user);

    expect($remaining['hourly_remaining'])->toBe(29);
    expect($remaining['daily_remaining'])->toBe(199);
});

it('does not report negative remaining', function () {
    for ($i = 0; $i < 40; $i++) {
        $this->service->trackSuccessfulNotification($this->user);
    }

    $remaining = $this->service->getRemainingTweets($this->user);

    expect($remaining['hourly_remaining'])->toBe(0);
    expect($remaining['daily_remaining'])->toBe(160);
});

it('increments backoff count exponentially', function () {
    $this->service->trackFailedNotification($this->user);

    $backoffKey = 'twitter_rate_limit:backoff:'.$this->user->id.':system';
    $data = Cache::get($backoffKey);

    expect($data['backoff_count'])->toBe(1);
    expect($data['backoff_until'])->toBeGreaterThan(now()->timestamp);
});
