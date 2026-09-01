<?php

use App\Models\Monitor;
use App\Services\SmartRetryAttempt;
use App\Services\SmartRetryService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class TestableSmartRetryService extends SmartRetryService
{
    public function setMockClient(Client $client): void
    {
        $this->client = $client;
    }
}

function makeSmartRetryService(array $responses)
{
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler, 'http_errors' => false]);

    $service = Mockery::mock(TestableSmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->setMockClient($client);

    return $service;
}

it('returns preset for known sensitivities', function () {
    expect(SmartRetryService::getPreset('low')['retries'])->toBe(5);
    expect(SmartRetryService::getPreset('medium')['retries'])->toBe(3);
    expect(SmartRetryService::getPreset('high')['retries'])->toBe(2);
});

it('falls back to medium preset for unknown sensitivity', function () {
    expect(SmartRetryService::getPreset('unknown'))->toBe(SmartRetryService::getPreset('medium'));
});

it('returns success on first head request', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = Mockery::mock(SmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->shouldReceive('tryHttpRequest')
        ->once()
        ->andReturn(new SmartRetryAttempt(
            success: true,
            method: 'HEAD',
            statusCode: 200,
            attemptNumber: 1,
        ));

    $result = $service->performSmartCheck($monitor, ['retries' => 3]);

    expect($result->isSuccess())->toBeTrue();
    expect($result->getAttemptCount())->toBe(1);
    expect($result->statusCode)->toBe(200);
});

it('returns failure when all attempts fail and tcp ping is blocked', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = Mockery::mock(SmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->shouldReceive('tryHttpRequest')
        ->andReturn(new SmartRetryAttempt(
            success: false,
            method: 'HEAD',
            statusCode: 500,
            errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
            errorMessage: 'Unexpected status code: 500',
            attemptNumber: 1,
        ));
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->isSuccess())->toBeFalse();
    expect($result->getAttemptCount())->toBe(1);
    expect($result->message)->toBe('Unexpected status code: 500');
    expect($result->statusCode)->toBe(500);
});

it('performs tcp ping as final attempt', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = Mockery::mock(SmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->shouldReceive('tryHttpRequest')
        ->andReturn(new SmartRetryAttempt(
            success: false,
            method: 'HEAD',
            statusCode: 503,
            errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
            errorMessage: 'Unexpected status code: 503',
            attemptNumber: 1,
        ));
    $service->shouldReceive('canPerformTcpPing')->andReturn(true);
    $service->shouldReceive('tryTcpPing')
        ->once()
        ->andReturn(new SmartRetryAttempt(
            success: true,
            type: SmartRetryAttempt::TYPE_TCP,
            attemptNumber: 2,
        ));

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->isSuccess())->toBeFalse();
    expect($result->getAttemptCount())->toBe(2);
    expect($result->message)->toBe('HTTP failure but TCP responsive - likely application issue');
});

it('builds success result with attempts', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = Mockery::mock(SmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->shouldReceive('tryHttpRequest')
        ->andReturn(new SmartRetryAttempt(
            success: true,
            method: 'GET',
            statusCode: 204,
            responseTime: 12.5,
            attemptNumber: 2,
        ));

    $result = $service->performSmartCheck($monitor, ['retries' => 2]);

    expect($result->isSuccess())->toBeTrue();
    expect($result->statusCode)->toBe(204);
    expect($result->responseTime)->toBe(12.5);
});

it('performs a real head request and accepts a 200 status', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = makeSmartRetryService([new Response(200, [], 'ok')]);
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->isSuccess())->toBeTrue();
    expect($result->getAttemptCount())->toBe(1);
    expect($result->statusCode)->toBe(200);
});

it('rejects an unexpected status code', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'expected_status_code' => 200,
    ]);

    $service = makeSmartRetryService([new Response(500, [], 'error')]);
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->isSuccess())->toBeFalse();
    expect($result->message)->toContain('Unexpected status code: 500');
});

it('fails when look_for_string is missing from the response', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'look_for_string' => 'HELLO_WORLD',
    ]);

    // HEAD times out, GET returns without the string, final HEAD fails
    $service = makeSmartRetryService([
        new ConnectException('Connection timed out', new Request('HEAD', 'https://example.com')),
        new Response(200, [], 'no such string'),
        new Response(500, [], 'error'),
    ]);
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 2]);

    expect($result->isSuccess())->toBeFalse();
    $messages = array_map(fn ($a) => $a->errorMessage, $result->attempts);
    expect($messages)->toContain('String not found: HELLO_WORLD');
});

it('passes when look_for_string is present in the response', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'look_for_string' => 'HELLO_WORLD',
    ]);

    // HEAD times out, then GET returns the expected string
    $service = makeSmartRetryService([
        new ConnectException('Connection timed out', new Request('HEAD', 'https://example.com')),
        new Response(200, [], 'prefix HELLO_WORLD suffix'),
        new Response(500, [], 'error'),
    ]);
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 2]);

    expect($result->isSuccess())->toBeTrue();
});

it('sends additional headers and payload on GET requests', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_additional_headers' => ['X-Custom' => 'value', 'timeout' => 5],
        'uptime_check_payload' => 'hello',
    ]);

    $service = makeSmartRetryService([new Response(200, [], 'ok')]);
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->isSuccess())->toBeTrue();
});

it('falls back to a tcp ping when http fails and tcp succeeds', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = Mockery::mock(SmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->shouldReceive('tryHttpRequest')
        ->andReturn(new SmartRetryAttempt(
            success: false,
            method: 'HEAD',
            statusCode: 503,
            errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
            errorMessage: 'Unexpected status code: 503',
            attemptNumber: 1,
        ));
    $service->shouldReceive('canPerformTcpPing')->andReturn(true);
    $service->shouldReceive('tryTcpPing')
        ->once()
        ->andReturn(new SmartRetryAttempt(
            success: true,
            type: SmartRetryAttempt::TYPE_TCP,
            attemptNumber: 2,
        ));

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->isSuccess())->toBeFalse();
    expect($result->message)->toBe('HTTP failure but TCP responsive - likely application issue');
});

it('attempts a real tcp ping to a local port', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    $service = Mockery::mock(TestableSmartRetryService::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();
    $service->shouldReceive('tryHttpRequest')
        ->andReturn(new SmartRetryAttempt(
            success: false,
            method: 'HEAD',
            statusCode: 503,
            errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
            errorMessage: 'Unexpected status code: 503',
            attemptNumber: 1,
        ));
    $service->shouldReceive('canPerformTcpPing')->andReturn(true);
    (new ReflectionClass(SmartRetryService::class))->getProperty('timeout')->setValue($service, 2);

    // Port 80 to example.com may not be reachable, but the code path
    // (fsockopen + error classification) is exercised either way.
    $result = $service->performSmartCheck($monitor, ['retries' => 1]);

    expect($result->getAttemptCount())->toBe(2);
    expect($result->isSuccess())->toBeFalse();
});

it('classifies connection errors correctly', function () {
    $service = new TestableSmartRetryService;

    $reflection = new ReflectionClass(SmartRetryService::class);
    $method = $reflection->getMethod('classifyConnectionError');
    $method->setAccessible(true);

    $timeout = new ConnectException('Operation timed out', new Request('GET', 'https://example.com'));
    expect($method->invoke($service, $timeout))->toBe(SmartRetryAttempt::ERROR_TIMEOUT);

    $refused = new ConnectException('Connection refused', new Request('GET', 'https://example.com'));
    expect($method->invoke($service, $refused))->toBe(SmartRetryAttempt::ERROR_CONNECTION_REFUSED);

    $dns = new ConnectException('Could not resolve host', new Request('GET', 'https://example.com'));
    expect($method->invoke($service, $dns))->toBe(SmartRetryAttempt::ERROR_DNS);

    $ssl = new ConnectException('SSL certificate problem', new Request('GET', 'https://example.com'));
    expect($method->invoke($service, $ssl))->toBe(SmartRetryAttempt::ERROR_SSL);
});

it('handles RequestException and general Exception gracefully during HTTP requests', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);

    // Test RequestException
    $req = new Request('GET', 'https://example.com');
    $reqException = new RequestException('Error Communicating with Server', $req, new Response(502));
    $service = makeSmartRetryService([$reqException]);
    $service->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result = $service->performSmartCheck($monitor, ['retries' => 1]);
    expect($result->isSuccess())->toBeFalse();
    expect($result->statusCode)->toBe(502);

    // Test generic exception
    $service2 = makeSmartRetryService([new Exception('Generic unexpected failure')]);
    $service2->shouldReceive('canPerformTcpPing')->andReturn(false);

    $result2 = $service2->performSmartCheck($monitor, ['retries' => 1]);
    expect($result2->isSuccess())->toBeFalse();
    expect($result2->message)->toBe('Generic unexpected failure');
});
