<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

beforeEach(function () {
    $this->tempDbPath = tempnam(sys_get_temp_dir(), 'backup_test_').'.sqlite';
    $sqlite = new SQLite3($this->tempDbPath);
    $sqlite->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT);');
    $sqlite->exec("INSERT INTO test_table (name) VALUES ('hello');");
    $sqlite->close();

    config([
        'database.connections.backup_sqlite' => [
            'driver' => 'sqlite',
            'database' => $this->tempDbPath,
            'prefix' => '',
        ],
        'backup.backup.name' => 'uptime-kita-test',
        'backup.backup.destination.disks' => ['local'],
        'backup.backup.source.databases' => ['backup_sqlite'],
    ]);

    Config::rebind();
});

afterEach(function () {
    if (isset($this->tempDbPath) && file_exists($this->tempDbPath)) {
        @unlink($this->tempDbPath);
    }

    Storage::disk('local')->deleteDirectory('uptime-kita-test');
});

test('backup run completes without error on local disk', function () {
    $exitCode = Artisan::call('backup:run', [
        '--only-to-disk' => 'local',
        '--only-db' => true,
        '--disable-notifications' => true,
    ]);

    expect($exitCode)->toBe(0);
});

test('backup clean completes without error on local disk', function () {
    $exitCode = Artisan::call('backup:clean', [
        '--disable-notifications' => true,
    ]);

    expect($exitCode)->toBe(0);
});

test('backup monitor reports healthy status on local disk', function () {
    Artisan::call('backup:run', [
        '--only-to-disk' => 'local',
        '--only-db' => true,
        '--disable-notifications' => true,
    ]);

    config([
        'backup.monitor_backups' => [
            [
                'name' => 'uptime-kita-test',
                'disks' => ['local'],
                'health_checks' => [
                    MaximumAgeInDays::class => 1,
                    MaximumStorageInMegabytes::class => 5000,
                ],
            ],
        ],
    ]);

    Config::rebind();

    $exitCode = Artisan::call('backup:monitor');

    expect($exitCode)->toBe(0);
});
