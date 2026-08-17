<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\BatchedMonitorStatusChanged;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramMessage;

$events = [
    ['url' => 'https://example.com', 'status' => 'DOWN'],
    ['url' => 'https://another.org', 'status' => 'UP'],
];

it('routes to mail for email channels', function () use ($events) {
    $user = User::factory()->create();
    NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'is_enabled' => true,
    ]);

    $notification = new BatchedMonitorStatusChanged($events);

    expect($notification->via($user))->toContain('mail');
});

it('routes to telegram for numeric telegram channels', function () use ($events) {
    $user = User::factory()->create();
    NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);

    $notification = new BatchedMonitorStatusChanged($events);

    expect($notification->via($user))->toContain('telegram');
});

it('skips telegram channel with non-numeric destination', function () use ($events) {
    $user = User::factory()->create();
    NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '@username',
        'is_enabled' => true,
    ]);

    $notification = new BatchedMonitorStatusChanged($events);

    expect($notification->via($user))->not->toContain('telegram');
});

it('skips disabled channels', function () use ($events) {
    $user = User::factory()->create();
    NotificationChannel::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'is_enabled' => false,
    ]);

    $notification = new BatchedMonitorStatusChanged($events);

    expect($notification->via($user))->not->toContain('mail');
});

it('builds a mail message with event details', function () use ($events) {
    $user = User::factory()->create(['name' => 'Budi']);

    $notification = new BatchedMonitorStatusChanged($events);
    $message = $notification->toMail($user);

    expect($message->subject)->toBe('Alert: 2 Monitor Status Changes');
    expect($message->greeting)->toBe('Halo, Budi');
    expect($message->introLines)->toContain('There have been 2 monitor status changes since the last update:');
    expect($message->actionText)->toBe('View Wallboard');
});

it('builds a telegram message with event details', function () use ($events) {
    $user = User::factory()->create();

    $notification = new BatchedMonitorStatusChanged($events);
    $message = $notification->toTelegram($user);

    expect($message)->toBeInstanceOf(TelegramMessage::class);
});

it('logs failures', function () {
    Log::spy();

    (new BatchedMonitorStatusChanged([]))->failed(new Exception('boom'));

    Log::shouldHaveReceived('error')
        ->once()
        ->with('Batched notification failed', ['exception' => 'boom']);
});
