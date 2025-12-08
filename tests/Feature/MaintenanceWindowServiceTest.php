<?php

use App\Models\Monitor;
use App\Services\MaintenanceWindowService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new MaintenanceWindowService;
});

it('detects monitor in one-time maintenance window', function () {
    Carbon::setTestNow('2025-12-15 03:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => '2025-12-15T02:00:00+00:00',
                'end' => '2025-12-15T04:00:00+00:00',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('detects monitor not in one-time maintenance window', function () {
    Carbon::setTestNow('2025-12-15 05:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => '2025-12-15T02:00:00+00:00',
                'end' => '2025-12-15T04:00:00+00:00',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('detects monitor in recurring maintenance window', function () {
    // Set to Sunday 03:00 UTC
    Carbon::setTestNow('2025-12-14 03:00:00'); // Sunday

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 0, // Sunday
                'start_time' => '02:00',
                'end_time' => '04:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('detects monitor not in recurring maintenance window on different day', function () {
    // Set to Monday 03:00 UTC
    Carbon::setTestNow('2025-12-15 03:00:00'); // Monday

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 0, // Sunday
                'start_time' => '02:00',
                'end_time' => '04:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('respects timezone in recurring maintenance window', function () {
    // Set to Sunday 09:00 UTC which is 16:00 in Asia/Jakarta (+7)
    Carbon::setTestNow('2025-12-14 09:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 0, // Sunday
                'start_time' => '15:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Jakarta',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('returns false when no maintenance windows configured', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => null,
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('returns false when maintenance windows is empty array', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('uses cached is_in_maintenance flag when valid', function () {
    Carbon::setTestNow('2025-12-15 03:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_in_maintenance' => true,
        'maintenance_ends_at' => '2025-12-15 04:00:00',
        'maintenance_windows' => [], // Empty but flag is set
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('updates maintenance status correctly', function () {
    Carbon::setTestNow('2025-12-15 03:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_in_maintenance' => false,
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => '2025-12-15T02:00:00+00:00',
                'end' => '2025-12-15T04:00:00+00:00',
            ],
        ],
    ]);

    $updated = $this->service->updateMaintenanceStatus($monitor);

    expect($updated)->toBeTrue();

    $monitor->refresh();
    expect($monitor->is_in_maintenance)->toBeTrue();
    expect($monitor->maintenance_starts_at)->not->toBeNull();
    expect($monitor->maintenance_ends_at)->not->toBeNull();
});

it('exits maintenance when window ends', function () {
    Carbon::setTestNow('2025-12-15 05:00:00'); // After window

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_in_maintenance' => true,
        'maintenance_starts_at' => '2025-12-15 02:00:00',
        'maintenance_ends_at' => '2025-12-15 04:00:00',
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => '2025-12-15T02:00:00+00:00',
                'end' => '2025-12-15T04:00:00+00:00',
            ],
        ],
    ]);

    $updated = $this->service->updateMaintenanceStatus($monitor);

    expect($updated)->toBeTrue();

    $monitor->refresh();
    expect($monitor->is_in_maintenance)->toBeFalse();
    expect($monitor->maintenance_starts_at)->toBeNull();
    expect($monitor->maintenance_ends_at)->toBeNull();
});

it('gets next maintenance window for one-time', function () {
    Carbon::setTestNow('2025-12-14 01:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => '2025-12-15T02:00:00+00:00',
                'end' => '2025-12-15T04:00:00+00:00',
            ],
        ],
    ]);

    $nextWindow = $this->service->getNextMaintenanceWindow($monitor);

    expect($nextWindow)->not->toBeNull();
    expect($nextWindow['type'])->toBe('one_time');
    expect($nextWindow)->toHaveKey('next_start');
    expect($nextWindow)->toHaveKey('next_end');
});

it('cleans up expired one-time maintenance windows', function () {
    Carbon::setTestNow('2025-12-20 00:00:00');

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => '2025-12-15T02:00:00+00:00',
                'end' => '2025-12-15T04:00:00+00:00', // Expired
            ],
            [
                'type' => 'recurring',
                'day_of_week' => 0,
                'start_time' => '02:00',
                'end_time' => '04:00',
                'timezone' => 'UTC',
            ],
            [
                'type' => 'one_time',
                'start' => '2025-12-25T02:00:00+00:00',
                'end' => '2025-12-25T04:00:00+00:00', // Future
            ],
        ],
    ]);

    $cleaned = $this->service->cleanupExpiredWindows();

    expect($cleaned)->toBe(1);

    $monitor->refresh();
    expect(count($monitor->maintenance_windows))->toBe(2); // Expired one removed

    // Verify the remaining windows
    $types = collect($monitor->maintenance_windows)->pluck('type')->toArray();
    expect($types)->toContain('recurring');
});
