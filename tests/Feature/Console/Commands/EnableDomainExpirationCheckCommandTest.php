<?php

use App\Models\Monitor;

it('enables domain expiration checking for all existing monitors', function () {
    Monitor::factory()->count(3)->create([
        'domain_expiration_check_enabled' => false,
    ]);

    $this->artisan('monitor:enable-domain-expiration-check')
        ->assertSuccessful()
        ->expectsOutput('Enabled domain expiration checking for 3 monitor(s).');

    expect(Monitor::where('domain_expiration_check_enabled', false)->count())->toBe(0)
        ->and(Monitor::where('domain_expiration_check_enabled', true)->count())->toBe(3);
});

it('reports when domain expiration checking is already enabled for all monitors', function () {
    Monitor::factory()->count(2)->create([
        'domain_expiration_check_enabled' => true,
    ]);

    $this->artisan('monitor:enable-domain-expiration-check')
        ->assertSuccessful()
        ->expectsOutput('Domain expiration checking was already enabled for all monitors.');
});

it('also enables domain expiration checking for disabled monitors', function () {
    Monitor::factory()->create([
        'domain_expiration_check_enabled' => false,
        'uptime_check_enabled' => false,
    ]);

    $this->artisan('monitor:enable-domain-expiration-check')
        ->assertSuccessful()
        ->expectsOutput('Enabled domain expiration checking for 1 monitor(s).');

    expect(Monitor::withoutGlobalScope('enabled')->where('domain_expiration_check_enabled', true)->count())->toBe(1);
});
