<?php

use App\Jobs\CheckDomainExpirationJob;
use App\Models\Monitor;
use App\Models\User;
use App\Notifications\DomainExpiringNotification;
use App\Services\DomainExpirationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\mock;

beforeEach(function () {
    Notification::fake();
    Carbon::setTestNow('2026-08-18 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow(null);
});

function runDomainExpirationJob(Monitor $monitor, ?Carbon $expirationDate): void
{
    $service = mock(DomainExpirationService::class);
    $service->shouldReceive('lookupExpirationDate')->andReturn($expirationDate);

    (new CheckDomainExpirationJob($monitor))->handle($service);
}

function attachSubscriber(Monitor $monitor, bool $active = true): User
{
    $user = User::factory()->create();
    $monitor->users()->attach($user->id, ['is_active' => $active]);
    $user->notificationChannels()->create([
        'type' => 'email',
        'destination' => $user->email,
        'is_enabled' => true,
    ]);

    return $user;
}

it('stores the looked up expiration date on the monitor', function () {
    $monitor = Monitor::factory()->create(['domain_expiration_check_enabled' => true]);

    runDomainExpirationJob($monitor, Carbon::parse('2027-09-01'));

    expect($monitor->fresh()->domain_expiration_date->toDateString())->toBe('2027-09-01');
    expect($monitor->fresh()->domain_expiration_lookup_error)->toBeNull();
});

it('records a lookup error when the expiration date cannot be resolved', function () {
    $monitor = Monitor::factory()->create();

    runDomainExpirationJob($monitor, null);

    expect($monitor->fresh()->domain_expiration_date)->toBeNull();
    expect($monitor->fresh()->domain_expiration_lookup_error)->not->toBeNull();
});

it('notifies subscribers at the 30 day threshold', function () {
    $monitor = Monitor::factory()->create();
    $user = attachSubscriber($monitor);

    runDomainExpirationJob($monitor, Carbon::parse('2026-09-17')); // 30 days left

    Notification::assertSentTo($user, DomainExpiringNotification::class);
});

it('does not notify when outside the reminder window', function () {
    $monitor = Monitor::factory()->create();
    attachSubscriber($monitor);

    runDomainExpirationJob($monitor, Carbon::parse('2026-09-07')); // 20 days left

    Notification::assertNothingSent();
});

it('sends only one reminder when a threshold and the daily reminder coincide', function () {
    $monitor = Monitor::factory()->create();
    $user = attachSubscriber($monitor);

    runDomainExpirationJob($monitor, Carbon::parse('2026-08-25')); // 7 days left

    Notification::assertSentToTimes($user, DomainExpiringNotification::class, 1);
    expect($monitor->domainExpirationReminders()->count())->toBe(1);
});

it('sends a daily reminder inside the final week and deduplicates within the same day', function () {
    $monitor = Monitor::factory()->create();
    $user = attachSubscriber($monitor);

    runDomainExpirationJob($monitor, Carbon::parse('2026-08-24')); // 6 days left
    runDomainExpirationJob($monitor, Carbon::parse('2026-08-24'));

    Notification::assertSentToTimes($user, DomainExpiringNotification::class, 1);

    // The next day another daily reminder is sent
    Carbon::setTestNow('2026-08-19 10:00:00');
    runDomainExpirationJob($monitor, Carbon::parse('2026-08-24'));

    Notification::assertSentToTimes($user, DomainExpiringNotification::class, 2);
});

it('continues to send daily reminders after the domain expires', function () {
    $monitor = Monitor::factory()->create();
    $user = attachSubscriber($monitor);

    runDomainExpirationJob($monitor, Carbon::parse('2026-08-10')); // already expired

    Notification::assertSentTo($user, DomainExpiringNotification::class);
});

it('clears previous reminders when the domain is renewed', function () {
    $monitor = Monitor::factory()->create();
    $user = attachSubscriber($monitor);

    // Domain expires in 7 days -> threshold + daily reminder sent
    runDomainExpirationJob($monitor, Carbon::parse('2026-08-25'));

    Notification::assertSentToTimes($user, DomainExpiringNotification::class, 1);
    expect($monitor->domainExpirationReminders()->count())->toBe(1);

    // Domain is renewed to a date far in the future -> reminders cleared, no new reminder
    runDomainExpirationJob($monitor, Carbon::parse('2027-08-25'));

    expect($monitor->domainExpirationReminders()->count())->toBe(0);
    Notification::assertSentToTimes($user, DomainExpiringNotification::class, 1);
});

it('notifies only users with an active subscription', function () {
    $monitor = Monitor::factory()->create();
    $activeUser = attachSubscriber($monitor, true);
    $inactiveUser = attachSubscriber($monitor, false);

    runDomainExpirationJob($monitor, Carbon::parse('2026-08-25'));

    Notification::assertSentTo($activeUser, DomainExpiringNotification::class);
    Notification::assertNotSentTo($inactiveUser, DomainExpiringNotification::class);
});
