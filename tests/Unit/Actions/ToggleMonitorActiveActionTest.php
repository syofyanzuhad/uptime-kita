<?php

use App\Actions\Monitors\ToggleMonitorActiveAction;
use App\Models\Monitor;
use App\Models\User;

test('toggle monitor action toggles status when user is subscribed', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $monitor->users()->attach($user->id, ['is_active' => true]);

    $action = new ToggleMonitorActiveAction;
    $result = $action->execute($user, $monitor->id);

    expect($result['success'])->toBeTrue()
        ->and($result['new_status'])->toBeFalse()
        ->and($monitor->fresh()->uptime_check_enabled)->toBeFalse();
});

test('toggle monitor action fails when user is not subscribed or monitor not found', function () {
    $user = User::factory()->create();
    $action = new ToggleMonitorActiveAction;

    $result1 = $action->execute($user, 999999);
    expect($result1['success'])->toBeFalse()
        ->and($result1['message'])->toBe('Monitor not found');

    $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $result2 = $action->execute($user, $monitor->id);
    expect($result2['success'])->toBeFalse()
        ->and($result2['message'])->toBe('User is not subscribed to this monitor');
});
