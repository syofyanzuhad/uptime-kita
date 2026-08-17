<?php

use App\Services\ServerResourceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function invokeProtectedMethod(object $object, string $method, array $parameters = []): mixed
{
    $reflection = new ReflectionMethod($object, $method);

    return $reflection->invokeArgs($object, $parameters);
}

test('parseProcStat parses cpu line from /proc/stat', function () {
    $service = new ServerResourceService;

    $result = invokeProtectedMethod($service, 'parseProcStat', [
        "cpu  1000 200 300 400 0 0 0 0 0 0\ncpu0 100 20 30 40 0 0 0 0 0 0\n",
    ]);

    expect($result)->toBe([
        'user' => 1000,
        'nice' => 200,
        'system' => 300,
        'idle' => 400,
    ]);
});

test('parseProcStat handles missing values', function () {
    $service = new ServerResourceService;

    $result = invokeProtectedMethod($service, 'parseProcStat', ['cpu  100 20']);

    expect($result['user'])->toBe(100)
        ->and($result['nice'])->toBe(20)
        ->and($result['system'])->toBe(0)
        ->and($result['idle'])->toBe(0);
});

test('formatBytes returns 0 B for zero bytes', function () {
    $service = new ServerResourceService;

    expect(invokeProtectedMethod($service, 'formatBytes', [0]))->toBe('0 B');
});

test('formatBytes formats various sizes', function () {
    $service = new ServerResourceService;

    expect(invokeProtectedMethod($service, 'formatBytes', [500]))->toBe('500 B');
    expect(invokeProtectedMethod($service, 'formatBytes', [2048]))->toBe('2 KB');
    expect(invokeProtectedMethod($service, 'formatBytes', [10 * 1024 * 1024]))->toBe('10 MB');
    expect(invokeProtectedMethod($service, 'formatBytes', [3 * 1024 * 1024 * 1024]))->toBe('3 GB');
    expect(invokeProtectedMethod($service, 'formatBytes', [5 * 1024 * 1024 * 1024 * 1024]))->toBe('5 TB');
});

test('formatUptime returns 0m for less than a minute', function () {
    $service = new ServerResourceService;

    expect(invokeProtectedMethod($service, 'formatUptime', [30]))->toBe('0m');
});

test('formatUptime includes days, hours, and minutes', function () {
    $service = new ServerResourceService;

    expect(invokeProtectedMethod($service, 'formatUptime', [90061]))->toBe('1d 1h 1m');
    expect(invokeProtectedMethod($service, 'formatUptime', [3661]))->toBe('1h 1m');
    expect(invokeProtectedMethod($service, 'formatUptime', [120]))->toBe('2m');
});

test('getLoadedExtensions returns booleans for important extensions', function () {
    $service = new ServerResourceService;

    $extensions = invokeProtectedMethod($service, 'getLoadedExtensions');

    expect($extensions)->toBeArray()
        ->and($extensions)->toHaveKey('pdo')
        ->and($extensions)->toHaveKey('curl')
        ->and($extensions)->toHaveKey('mbstring')
        ->and($extensions['pdo'])->toBeBool();
});

test('getDatabaseInfo reports error when query fails', function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', '/nonexistent/path/database.sqlite');

    $service = new ServerResourceService;

    $info = invokeProtectedMethod($service, 'getDatabaseInfo');

    expect($info['connection'])->toBe('sqlite')
        ->and($info['status'])->toBe('connected')
        ->and($info['size'])->toBe(0);
});

test('getQueueInfo counts pending database jobs', function () {
    config()->set('queue.default', 'database');

    DB::shouldReceive('table')
        ->with('jobs')
        ->once()
        ->andReturn(Mockery::mock(['count' => 3]));
    DB::shouldReceive('table')
        ->with('failed_jobs')
        ->once()
        ->andReturn(Mockery::mock(['count' => 1]));

    $service = new ServerResourceService;

    $info = invokeProtectedMethod($service, 'getQueueInfo');

    expect($info['driver'])->toBe('database')
        ->and($info['pending_jobs'])->toBe(3)
        ->and($info['failed_jobs'])->toBe(1);
});

test('getQueueInfo falls back to zero when tables are missing', function () {
    config()->set('queue.default', 'database');

    DB::shouldReceive('table')
        ->with('jobs')
        ->once()
        ->andThrow(new RuntimeException('no such table'));

    $service = new ServerResourceService;

    $info = invokeProtectedMethod($service, 'getQueueInfo');

    expect($info['pending_jobs'])->toBe(0)
        ->and($info['failed_jobs'])->toBe(0);
});

test('getCacheInfo reports working cache', function () {
    $service = new ServerResourceService;

    $info = invokeProtectedMethod($service, 'getCacheInfo');

    expect($info)->toHaveKey('driver')
        ->and($info)->toHaveKey('status');
});

test('getCacheInfo reports error when cache fails', function () {
    Cache::shouldReceive('put')->andThrow(new RuntimeException('cache down'));

    $service = new ServerResourceService;

    $info = invokeProtectedMethod($service, 'getCacheInfo');

    expect($info['status'])->toBe('error');
});

test('getLoadAverage returns rounded values', function () {
    $service = new ServerResourceService;

    $load = invokeProtectedMethod($service, 'getLoadAverage');

    expect($load)->toHaveKeys(['1min', '5min', '15min'])
        ->and($load['1min'])->toBeFloat();
});
