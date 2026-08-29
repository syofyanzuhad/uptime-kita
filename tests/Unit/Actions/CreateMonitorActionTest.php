<?php

use App\Actions\Monitors\CreateMonitorAction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('it creates monitor, attaches tags and sets correct attributes', function () {
    Artisan::shouldReceive('call')->with('monitor:check-certificate', Mockery::type('array'))->once();

    $user = User::factory()->create();
    $action = new CreateMonitorAction;

    $monitor = $action->execute($user, [
        'url' => 'https://action-test.com',
        'is_public' => true,
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
        'domain_expiration_check_enabled' => false,
        'uptime_check_interval' => 10,
        'tags' => ['API', 'Production'],
    ]);

    expect($monitor->id)->not->toBeNull()
        ->and((string) $monitor->url)->toBe('https://action-test.com')
        ->and($monitor->is_public)->toBeTrue()
        ->and($monitor->uptime_check_interval_in_minutes)->toBe(10);
});
