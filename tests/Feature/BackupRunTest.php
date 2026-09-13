<?php

use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

test('backup run completes without error on local disk', function () {
    config([
        'backup.backup.destination.disks' => ['local'],
        'backup.backup.source.databases' => [
            config('database.default'),
        ],
    ]);

    $exitCode = Artisan::call('backup:run', [
        '--only-to-disk' => 'local',
        '--only-db' => true,
        '--disable-notifications' => true,
    ]);

    expect($exitCode)->toBe(0);
});

test('backup clean completes without error on local disk', function () {
    config([
        'backup.backup.destination.disks' => ['local'],
    ]);

    $exitCode = Artisan::call('backup:clean', [
        '--disable-notifications' => true,
    ]);

    expect($exitCode)->toBe(0);
});

test('backup monitor reports healthy status on local disk', function () {
    config([
        'backup.monitor_backups' => [
            [
                'name' => config('backup.backup.name', 'uptime-kita'),
                'disks' => ['local'],
                'health_checks' => [
                    MaximumAgeInDays::class => 1,
                    MaximumStorageInMegabytes::class => 5000,
                ],
            ],
        ],
    ]);

    $exitCode = Artisan::call('backup:monitor');

    expect($exitCode)->toBe(0);
});
