<?php

use App\Models\Monitor;
use App\Models\MonitorDomainExpirationReminder;
use Illuminate\Support\Carbon;

it('has fillable attributes', function () {
    $reminder = MonitorDomainExpirationReminder::factory()->create();

    expect($reminder->monitor_id)->not->toBeNull();
    expect($reminder->reminder_key)->toBe('threshold_30');
    expect($reminder->sent_at)->not->toBeNull();
});

it('casts sent_at to a datetime', function () {
    $reminder = MonitorDomainExpirationReminder::factory()->create([
        'sent_at' => now(),
    ]);

    expect($reminder->sent_at)->toBeInstanceOf(Carbon::class);
});

it('belongs to a monitor', function () {
    $monitor = Monitor::factory()->create();
    $reminder = MonitorDomainExpirationReminder::factory()->create([
        'monitor_id' => $monitor->id,
    ]);

    expect($reminder->monitor->is($monitor))->toBeTrue();
});

it('can be queried through the monitor relationship', function () {
    $monitor = Monitor::factory()->create();
    MonitorDomainExpirationReminder::factory()->create([
        'monitor_id' => $monitor->id,
        'reminder_key' => 'threshold_30',
    ]);
    MonitorDomainExpirationReminder::factory()->create([
        'monitor_id' => $monitor->id,
        'reminder_key' => 'threshold_14',
    ]);

    expect($monitor->domainExpirationReminders)->toHaveCount(2);
});
