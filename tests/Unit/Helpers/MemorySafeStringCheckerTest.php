<?php

use App\Helpers\UptimeResponseCheckers\MemorySafeStringChecker;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Spatie\UptimeMonitor\Models\Monitor;

test('validates response when look_for_string is empty', function () {
    $checker = new MemorySafeStringChecker;
    $monitor = new Monitor;
    $monitor->look_for_string = '';

    $stream = Utils::streamFor('hello world');
    $response = new Response(200, [], $stream);

    expect($checker->isValidResponse($response, $monitor))->toBeTrue();
});

test('validates response when look_for_string is present in body', function () {
    $checker = new MemorySafeStringChecker;
    $monitor = new Monitor;
    $monitor->look_for_string = 'UPTIME_OK';

    $stream = Utils::streamFor('{"status":"UPTIME_OK","version":"1.0"}');
    $response = new Response(200, [], $stream);

    expect($checker->isValidResponse($response, $monitor))->toBeTrue();
});

test('fails response when look_for_string is missing', function () {
    $checker = new MemorySafeStringChecker;
    $monitor = new Monitor;
    $monitor->look_for_string = 'MISSING_TOKEN';

    $stream = Utils::streamFor('{"status":"OK"}');
    $response = new Response(200, [], $stream);

    expect($checker->isValidResponse($response, $monitor))->toBeFalse()
        ->and($checker->getFailureReason($response, $monitor))->toContain('MISSING_TOKEN');
});
