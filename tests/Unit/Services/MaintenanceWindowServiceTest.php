<?php

use App\Models\Monitor;
use App\Services\MaintenanceWindowService;

beforeEach(function () {
    $this->service = new MaintenanceWindowService;
});

it('returns false when no windows configured', function () {
    $monitor = Monitor::factory()->create(['maintenance_windows' => null]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('returns true during a one-time window', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->subHour()->toISOString(),
                'end' => now()->addHour()->toISOString(),
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('returns false outside a one-time window', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->subDays(2)->toISOString(),
                'end' => now()->subDay()->toISOString(),
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('returns false when one-time window is missing dates', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            ['type' => 'one_time', 'start' => null, 'end' => null],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('returns true during a recurring window on the right day', function () {
    $this->travelTo(now()->startOfWeek()->setTime(10, 0)); // Monday 10:00

    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 1, // Monday
                'start_time' => '09:00',
                'end_time' => '11:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('returns false on the wrong day for a recurring window', function () {
    $this->travelTo(now()->startOfWeek()->setTime(10, 0)); // Monday 10:00

    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 2, // Tuesday
                'start_time' => '09:00',
                'end_time' => '11:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('handles overnight recurring windows', function () {
    $this->travelTo(now()->startOfWeek()->setTime(23, 30)); // Monday 23:30

    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 1, // Monday
                'start_time' => '23:00',
                'end_time' => '02:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('returns false for recurring windows missing fields', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            ['type' => 'recurring'],
        ],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('uses the cached maintenance flag when still valid', function () {
    $monitor = Monitor::factory()->create([
        'is_in_maintenance' => true,
        'maintenance_ends_at' => now()->addHour(),
        'maintenance_windows' => [],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeTrue();
});

it('returns false when cached flag is stale', function () {
    $monitor = Monitor::factory()->create([
        'is_in_maintenance' => true,
        'maintenance_ends_at' => now()->subHour(),
        'maintenance_windows' => [],
    ]);

    expect($this->service->isInMaintenance($monitor))->toBeFalse();
});

it('updates maintenance status when entering a window', function () {
    $monitor = Monitor::factory()->create([
        'is_in_maintenance' => false,
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->subHour()->toISOString(),
                'end' => now()->addHour()->toISOString(),
            ],
        ],
    ]);

    $result = $this->service->updateMaintenanceStatus($monitor);

    expect($result)->toBeTrue();
    $monitor->refresh();
    expect($monitor->is_in_maintenance)->toBeTrue();
    expect($monitor->maintenance_ends_at)->not->toBeNull();
});

it('does not update when status is unchanged', function () {
    $monitor = Monitor::factory()->create([
        'is_in_maintenance' => false,
        'maintenance_windows' => null,
    ]);

    $result = $this->service->updateMaintenanceStatus($monitor);

    expect($result)->toBeFalse();
});

it('gets next one-time maintenance window', function () {
    $futureStart = now()->addDays(2);
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => $futureStart->toISOString(),
                'end' => $futureStart->addHours(2)->toISOString(),
            ],
        ],
    ]);

    $next = $this->service->getNextMaintenanceWindow($monitor);

    expect($next)->not->toBeNull();
    expect($next['next_start'])->not->toBeNull();
    expect($next['next_end'])->not->toBeNull();
});

it('returns null when no future windows exist', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->subDays(2)->toISOString(),
                'end' => now()->subDay()->toISOString(),
            ],
        ],
    ]);

    expect($this->service->getNextMaintenanceWindow($monitor))->toBeNull();
});

it('cleans up expired one-time windows', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->subDays(2)->toISOString(),
                'end' => now()->subDay()->toISOString(),
            ],
            [
                'type' => 'recurring',
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    $cleaned = $this->service->cleanupExpiredWindows();

    expect($cleaned)->toBe(1);
    $monitor->refresh();
    expect($monitor->maintenance_windows)->toHaveCount(1);
    expect($monitor->maintenance_windows[0]['type'])->toBe('recurring');
});

it('does not clean up when all windows are valid', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->addDay()->toISOString(),
                'end' => now()->addDays(2)->toISOString(),
            ],
        ],
    ]);

    expect($this->service->cleanupExpiredWindows())->toBe(0);
});

it('updates all monitor maintenance statuses', function () {
    Monitor::factory()->create([
        'is_in_maintenance' => false,
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->subHour()->toISOString(),
                'end' => now()->addHour()->toISOString(),
            ],
        ],
    ]);

    $updated = $this->service->updateAllMaintenanceStatuses();

    expect($updated)->toBe(1);
});

it('gets the next recurring window start', function () {
    $this->travelTo(now()->startOfWeek()->setTime(8, 0)); // Monday 08:00

    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'recurring',
                'day_of_week' => 1, // Monday
                'start_time' => '09:00',
                'end_time' => '11:00',
                'timezone' => 'UTC',
            ],
        ],
    ]);

    $next = $this->service->getNextMaintenanceWindow($monitor);

    expect($next)->not->toBeNull();
    expect($next['next_start'])->not->toBeNull();
});

it('picks the earliest of multiple windows', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            [
                'type' => 'one_time',
                'start' => now()->addDays(3)->toISOString(),
                'end' => now()->addDays(3)->addHours(2)->toISOString(),
            ],
            [
                'type' => 'one_time',
                'start' => now()->addDay()->toISOString(),
                'end' => now()->addDay()->addHours(2)->toISOString(),
            ],
        ],
    ]);

    $next = $this->service->getNextMaintenanceWindow($monitor);

    expect($next)->not->toBeNull();
    expect($next['type'])->toBe('one_time');
});

it('returns null for malformed windows in next-window lookup', function () {
    $monitor = Monitor::factory()->create([
        'maintenance_windows' => [
            ['type' => 'unknown-type'],
        ],
    ]);

    expect($this->service->getNextMaintenanceWindow($monitor))->toBeNull();
});
