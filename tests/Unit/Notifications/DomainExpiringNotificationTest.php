<?php

use App\Models\Monitor;
use App\Models\User;
use App\Notifications\DomainExpiringNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Telegram\TelegramMessage;

it('delivers via email and telegram when channels are enabled', function () {
    $user = User::factory()->create();
    $user->notificationChannels()->create([
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => true,
    ]);
    $user->notificationChannels()->create([
        'type' => 'telegram',
        'destination' => '123456789',
        'is_enabled' => true,
    ]);
    $user->notificationChannels()->create([
        'type' => 'telegram',
        'destination' => 'invalid-destination',
        'is_enabled' => true,
    ]);

    $monitor = Monitor::factory()->create(['domain_expiration_date' => now()->addDays(14)]);
    $notification = new DomainExpiringNotification($monitor, 14);

    expect($notification->via($user))->toBe(['mail', 'telegram']);
});

it('does not deliver when no channels are enabled', function () {
    $user = User::factory()->create();
    $user->notificationChannels()->create([
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => false,
    ]);

    $monitor = Monitor::factory()->create(['domain_expiration_date' => now()->addDays(14)]);
    $notification = new DomainExpiringNotification($monitor, 14);

    expect($notification->via($user))->toBe([]);
});

it('builds a mail message with the expiry details', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'domain_expiration_date' => '2027-09-01',
    ]);

    $notification = new DomainExpiringNotification($monitor, 14);

    $message = $notification->toMail($user);

    expect($message)->toBeInstanceOf(MailMessage::class);
    expect($message->subject)->toContain('example.com');
    expect($message->introLines[1])->toContain('example.com');
    expect($message->introLines[2])->toContain('14 hari');
});

it('builds a telegram message with the expiry details', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_public' => true,
        'domain_expiration_date' => '2027-09-01',
    ]);

    $notification = new DomainExpiringNotification($monitor, 14);

    $message = $notification->toTelegram($user);

    expect($message)->toBeInstanceOf(TelegramMessage::class);
    expect($message->toArray()['text'])->toContain('example.com');
    expect($message->toArray()['text'])->toContain('14 hari');
});

it('marks the domain as expired in the message when days are negative', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'domain_expiration_date' => now()->subDay(),
    ]);

    $notification = new DomainExpiringNotification($monitor, -1);

    expect($notification->toMail($user)->subject)->toContain('Telah Kedaluwarsa');
});

it('stores the notification payload in the database channel', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'domain_expiration_date' => '2027-09-01',
    ]);

    $notification = new DomainExpiringNotification($monitor, 14);

    $payload = $notification->toArray($user);

    expect($payload['monitor_id'])->toBe($monitor->id);
    expect($payload['days_left'])->toBe(14);
    expect($payload['domain_expiration_date'])->toBe('2027-09-01 00:00:00');
});

it('can be sent through the notification system', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->notificationChannels()->create([
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => true,
    ]);

    $monitor = Monitor::factory()->create(['domain_expiration_date' => now()->addDays(14)]);

    $user->notify(new DomainExpiringNotification($monitor, 14));

    Notification::assertSentTo($user, DomainExpiringNotification::class);
});
