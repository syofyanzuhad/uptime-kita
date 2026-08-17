<?php

use App\Services\MaintenanceWindowService;

it('updates maintenance status for all monitors', function () {
    $this->mock(MaintenanceWindowService::class, function ($mock) {
        $mock->shouldReceive('updateAllMaintenanceStatuses')->once()->andReturn(3);
        $mock->shouldReceive('cleanupExpiredWindows')->never();
    });

    $this->artisan('monitor:update-maintenance-status')
        ->assertSuccessful()
        ->expectsOutput('Updating maintenance status for monitors...')
        ->expectsOutput('Updated 3 monitor(s) maintenance status.');
});

it('cleans up expired windows when cleanup option is passed', function () {
    $this->mock(MaintenanceWindowService::class, function ($mock) {
        $mock->shouldReceive('updateAllMaintenanceStatuses')->once()->andReturn(2);
        $mock->shouldReceive('cleanupExpiredWindows')->once()->andReturn(1);
    });

    $this->artisan('monitor:update-maintenance-status', ['--cleanup' => true])
        ->assertSuccessful()
        ->expectsOutput('Cleaning up expired one-time maintenance windows...')
        ->expectsOutput('Cleaned up 1 monitor(s) with expired windows.');
});
