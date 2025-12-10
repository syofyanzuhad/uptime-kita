<?php

use App\Models\Monitor;
use App\Models\User;
use App\Policies\MonitorPolicy;

test('only user with is_admin true can update public monitors', function () {
    // Create users
    $adminUser = User::factory()->create(['id' => 1, 'is_admin' => 1]);
    $regularUser = User::factory()->create(['id' => 2]);

    // Temporarily disable the created event to prevent automatic user attachment
    Monitor::withoutEvents(function () use ($adminUser, $regularUser) {
        // Create a public monitor without global scope
        $publicMonitor = Monitor::withoutGlobalScope('user')->create([
            'url' => 'https://example.com',
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        // Manually attach users to monitor
        $publicMonitor->users()->attach($regularUser->id, ['is_active' => true]);

        $policy = new MonitorPolicy;

        // Admin user should be able to update public monitor
        expect($policy->update($adminUser, $publicMonitor))->toBeTrue();

        // Regular user should not be able to update public monitor
        expect($policy->update($regularUser, $publicMonitor))->toBeFalse();
    });
});

test('only owner can update private monitors', function () {
    // Create users
    $owner = User::factory()->create(['id' => 3]);
    $nonOwner = User::factory()->create(['id' => 4]);

    // Temporarily disable the created event to prevent automatic user attachment
    Monitor::withoutEvents(function () use ($owner, $nonOwner) {
        // Create a private monitor without global scope
        $privateMonitor = Monitor::withoutGlobalScope('user')->create([
            'url' => 'https://private.com',
            'is_public' => false,
            'uptime_check_enabled' => true,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        // Manually attach users to monitor (owner first to be the owner)
        $privateMonitor->users()->attach($owner->id, ['is_active' => true]);
        $privateMonitor->users()->attach($nonOwner->id, ['is_active' => true]);

        $policy = new MonitorPolicy;

        // Owner should be able to update private monitor
        expect($policy->update($owner, $privateMonitor))->toBeTrue();

        // Non-owner should not be able to update private monitor
        expect($policy->update($nonOwner, $privateMonitor))->toBeFalse();
    });
});

test('only user with is_admin true can delete public monitors', function () {
    // Create users
    $adminUser = User::factory()->create(['id' => 1, 'is_admin' => 1]);
    $regularUser = User::factory()->create(['id' => 2]);

    // Temporarily disable the created event to prevent automatic user attachment
    Monitor::withoutEvents(function () use ($adminUser, $regularUser) {
        // Create a public monitor without global scope
        $publicMonitor = Monitor::withoutGlobalScope('user')->create([
            'url' => 'https://example.com',
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        // Manually attach users to monitor
        $publicMonitor->users()->attach($regularUser->id, ['is_active' => true]);

        $policy = new MonitorPolicy;

        // Admin user should be able to delete public monitor
        expect($policy->delete($adminUser, $publicMonitor))->toBeTrue();

        // Regular user should not be able to delete public monitor
        expect($policy->delete($regularUser, $publicMonitor))->toBeFalse();
    });
});

test('only owner can delete private monitors', function () {
    // Create users
    $owner = User::factory()->create(['id' => 3]);
    $nonOwner = User::factory()->create(['id' => 4]);

    // Temporarily disable the created event to prevent automatic user attachment
    Monitor::withoutEvents(function () use ($owner, $nonOwner) {
        // Create a private monitor without global scope
        $privateMonitor = Monitor::withoutGlobalScope('user')->create([
            'url' => 'https://private.com',
            'is_public' => false,
            'uptime_check_enabled' => true,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        // Manually attach users to monitor (owner first to be the owner)
        $privateMonitor->users()->attach($owner->id, ['is_active' => true]);
        $privateMonitor->users()->attach($nonOwner->id, ['is_active' => true]);

        $policy = new MonitorPolicy;

        // Owner should be able to delete private monitor
        expect($policy->delete($owner, $privateMonitor))->toBeTrue();

        // Non-owner should not be able to delete private monitor
        expect($policy->delete($nonOwner, $privateMonitor))->toBeFalse();
    });
});

test('monitor owner is correctly determined', function () {
    // Create users
    $owner = User::factory()->create(['id' => 5]);
    $nonOwner = User::factory()->create(['id' => 6]);

    // Temporarily disable the created event to prevent automatic user attachment
    Monitor::withoutEvents(function () use ($owner, $nonOwner) {
        // Create a private monitor without global scope
        $privateMonitor = Monitor::withoutGlobalScope('user')->create([
            'url' => 'https://private.com',
            'is_public' => false,
            'uptime_check_enabled' => true,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        // Manually attach users to monitor (owner first to be the owner)
        $privateMonitor->users()->attach($owner->id, ['is_active' => true]);
        $privateMonitor->users()->attach($nonOwner->id, ['is_active' => true]);

        // Check that the owner is correctly determined
        expect($privateMonitor->owner->id)->toBe($owner->id);
        expect($privateMonitor->isOwnedBy($owner))->toBeTrue();
        expect($privateMonitor->isOwnedBy($nonOwner))->toBeFalse();
    });
});
