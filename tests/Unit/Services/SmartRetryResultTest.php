<?php

use App\Services\SmartRetryAttempt;
use App\Services\SmartRetryResult;

it('reports success state', function () {
    expect((new SmartRetryResult(true))->isSuccess())->toBeTrue();
    expect((new SmartRetryResult(false))->isSuccess())->toBeFalse();
});

it('counts attempts', function () {
    $attempts = [
        new SmartRetryAttempt(success: false, attemptNumber: 1),
        new SmartRetryAttempt(success: true, attemptNumber: 2),
    ];

    $result = new SmartRetryResult(false, $attempts);

    expect($result->getAttemptCount())->toBe(2);
});

it('returns the last attempt', function () {
    $first = new SmartRetryAttempt(success: false, attemptNumber: 1);
    $second = new SmartRetryAttempt(success: true, attemptNumber: 2);

    $result = new SmartRetryResult(false, [$first, $second]);

    expect($result->getLastAttempt())->toBe($second);
});

it('returns the successful attempt', function () {
    $first = new SmartRetryAttempt(success: false, attemptNumber: 1);
    $second = new SmartRetryAttempt(success: true, attemptNumber: 2);

    $result = new SmartRetryResult(false, [$first, $second]);

    expect($result->getSuccessfulAttempt())->toBe($second);
});

it('returns null successful attempt when none succeeded', function () {
    $first = new SmartRetryAttempt(success: false, attemptNumber: 1);

    $result = new SmartRetryResult(false, [$first]);

    expect($result->getSuccessfulAttempt())->toBeNull();
});

it('converts to array', function () {
    $attempt = new SmartRetryAttempt(
        success: true,
        method: 'HEAD',
        statusCode: 200,
        responseTime: 10.0,
        attemptNumber: 1,
    );

    $result = new SmartRetryResult(true, [$attempt], 'ok', 200, 10.0);

    expect($result->toArray())->toBe([
        'success' => true,
        'message' => 'ok',
        'status_code' => 200,
        'response_time' => 10.0,
        'attempt_count' => 1,
        'attempts' => [$attempt->toArray()],
    ]);
});

it('attempt exposes error type helpers', function () {
    $timeout = new SmartRetryAttempt(success: false, errorType: SmartRetryAttempt::ERROR_TIMEOUT);
    $refused = new SmartRetryAttempt(success: false, errorType: SmartRetryAttempt::ERROR_CONNECTION_REFUSED);
    $dns = new SmartRetryAttempt(success: false, errorType: SmartRetryAttempt::ERROR_DNS);
    $ssl = new SmartRetryAttempt(success: false, errorType: SmartRetryAttempt::ERROR_SSL);
    $http = new SmartRetryAttempt(success: false, errorType: SmartRetryAttempt::ERROR_HTTP_STATUS);

    expect($timeout->isTimeout())->toBeTrue();
    expect($refused->isConnectionRefused())->toBeTrue();
    expect($dns->isDnsError())->toBeTrue();
    expect($ssl->isSslError())->toBeTrue();
    expect($http->isHttpStatusError())->toBeTrue();
});

it('attempt converts to array', function () {
    $attempt = new SmartRetryAttempt(
        success: false,
        type: SmartRetryAttempt::TYPE_TCP,
        method: 'GET',
        statusCode: 500,
        responseTime: 1.5,
        errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
        errorMessage: 'boom',
        attemptNumber: 3,
    );

    expect($attempt->toArray())->toBe([
        'success' => false,
        'type' => 'tcp',
        'method' => 'GET',
        'status_code' => 500,
        'response_time' => 1.5,
        'error_type' => 'http_status',
        'error_message' => 'boom',
        'attempt_number' => 3,
    ]);
});
