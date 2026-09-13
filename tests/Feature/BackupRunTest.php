<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

beforeEach(function () {
    config()->set('backup.backup.name', 'uptime-kita-test');
    config()->set('backup.backup.destination.disks', ['local']);
    config()->set('backup.backup.source.files.include', [database_path('migrations')]);
    config()->set('backup.backup.source.files.exclude', []);
    config()->set('backup.monitor_backups', [
        [
            'name' => 'uptime-kita-test',
            'disks' => ['local'],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ]);
});

afterEach(function () {
    Storage::disk('local')->deleteDirectory('uptime-kita-test');
});

test('backup run completes without error on local disk', function () {
    $exitCode = Artisan::call('backup:run', [
        '--only-to-disk' => 'local',
        '--only-files' => true,
        '--disable-notifications' => true,
        '--config' => 'backup',
    ]);

    expect($exitCode)->toBe(0, Artisan::output());
    expect(Storage::disk('local')->allFiles('uptime-kita-test'))->not->toBeEmpty();
});

test('backup clean completes without error on local disk', function () {
    $exitCode = Artisan::call('backup:clean', [
        '--disable-notifications' => true,
    ]);

    expect($exitCode)->toBe(0, Artisan::output());
});

test('backup monitor reports healthy status on local disk', function () {
    Artisan::call('backup:run', [
        '--only-to-disk' => 'local',
        '--only-files' => true,
        '--disable-notifications' => true,
        '--config' => 'backup',
    ]);

    $exitCode = Artisan::call('backup:monitor');

    expect($exitCode)->toBe(0, Artisan::output());
});
