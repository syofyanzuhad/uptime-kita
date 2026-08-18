<?php

use App\Jobs\SendTelemetryPingJob;
use App\Services\TelemetryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function mockTelemetryService(array $overrides = []): object
{
    $defaults = [
        'isEnabled' => true,
        'collectData' => ['instance_id' => 'abc', 'stats' => []],
        'getEndpoint' => 'https://telemetry.example.com/ping',
    ];

    $config = array_merge($defaults, $overrides);

    return mock(TelemetryService::class, function ($mock) use ($config) {
        $mock->shouldReceive('isEnabled')->andReturn($config['isEnabled']);
        if ($config['isEnabled']) {
            $mock->shouldReceive('collectData')->andReturn($config['collectData']);
            $mock->shouldReceive('getEndpoint')->andReturn($config['getEndpoint']);
        }
    });
}

it('skips ping when telemetry is disabled', function () {
    Log::spy();
    $service = mockTelemetryService(['isEnabled' => false]);

    (new SendTelemetryPingJob)->handle($service);

    Log::shouldHaveReceived('debug')->once()->with('Telemetry is disabled, skipping ping.');
});

it('logs data in debug mode instead of sending', function () {
    config(['telemetry.debug' => true]);
    Log::spy();

    $service = mockTelemetryService();
    $service->shouldReceive('recordPing')->once();

    (new SendTelemetryPingJob)->handle($service);

    Log::shouldHaveReceived('debug')->once()->withArgs(function ($message) {
        return str_contains($message, 'Telemetry Debug');
    });
});

it('sends telemetry ping successfully', function () {
    Http::fake([
        'telemetry.example.com/*' => Http::response(['ok' => true], 200),
    ]);

    $service = mockTelemetryService();
    $service->shouldReceive('recordPing')->once();

    (new SendTelemetryPingJob)->handle($service);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://telemetry.example.com/ping';
    });
});

it('logs a warning when the ping fails with an error status', function () {
    Http::fake([
        'telemetry.example.com/*' => Http::response(['error' => true], 500),
    ]);
    Log::spy();

    $service = mockTelemetryService();
    $service->shouldNotReceive('recordPing');

    (new SendTelemetryPingJob)->handle($service);

    Log::shouldHaveReceived('warning')->once()->with('Telemetry ping failed with status: 500');
});

it('rethrows exceptions to trigger retry', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $service = mockTelemetryService();

    expect(fn () => (new SendTelemetryPingJob)->handle($service))
        ->toThrow(ConnectionException::class);
});

it('logs final failure after all retries', function () {
    Log::spy();

    (new SendTelemetryPingJob)->failed(new Exception('connection refused'));

    Log::shouldHaveReceived('debug')
        ->once()
        ->with('Telemetry ping failed after all retries: connection refused');
});
