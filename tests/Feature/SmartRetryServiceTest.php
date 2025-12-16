<?php

use App\Models\Monitor;
use App\Services\SmartRetryAttempt;
use App\Services\SmartRetryResult;
use App\Services\SmartRetryService;

beforeEach(function () {
    $this->service = new SmartRetryService;
});

describe('SmartRetryService', function () {
    describe('getPreset', function () {
        it('returns low sensitivity preset', function () {
            $preset = SmartRetryService::getPreset('low');

            expect($preset)->toBeArray()
                ->and($preset['confirmation_delay'])->toBe(60)
                ->and($preset['retries'])->toBe(5)
                ->and($preset['backoff_multiplier'])->toBe(2)
                ->and($preset['initial_delay_ms'])->toBe(200);
        });

        it('returns medium sensitivity preset', function () {
            $preset = SmartRetryService::getPreset('medium');

            expect($preset)->toBeArray()
                ->and($preset['confirmation_delay'])->toBe(30)
                ->and($preset['retries'])->toBe(3)
                ->and($preset['backoff_multiplier'])->toBe(2)
                ->and($preset['initial_delay_ms'])->toBe(100);
        });

        it('returns high sensitivity preset', function () {
            $preset = SmartRetryService::getPreset('high');

            expect($preset)->toBeArray()
                ->and($preset['confirmation_delay'])->toBe(15)
                ->and($preset['retries'])->toBe(2)
                ->and($preset['backoff_multiplier'])->toBe(1.5)
                ->and($preset['initial_delay_ms'])->toBe(50);
        });

        it('returns medium preset for unknown sensitivity', function () {
            $preset = SmartRetryService::getPreset('unknown');

            expect($preset)->toBeArray()
                ->and($preset['confirmation_delay'])->toBe(30);
        });
    });

    describe('performSmartCheck', function () {
        it('returns failure for a non-existent domain', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://this-domain-does-not-exist-12345.invalid',
                'uptime_check_enabled' => true,
            ]);

            $result = $this->service->performSmartCheck($monitor, [
                'retries' => 1,
                'initial_delay_ms' => 50,
            ]);

            expect($result)->toBeInstanceOf(SmartRetryResult::class)
                ->and($result->isSuccess())->toBeFalse()
                ->and($result->getAttemptCount())->toBeGreaterThanOrEqual(1);
        });

        it('includes TCP ping attempt for failed HTTP checks', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://this-domain-does-not-exist-12345.invalid',
                'uptime_check_enabled' => true,
            ]);

            $result = $this->service->performSmartCheck($monitor, [
                'retries' => 1,
                'initial_delay_ms' => 50,
            ]);

            // Should have at least HTTP attempt and TCP attempt
            expect($result->getAttemptCount())->toBeGreaterThanOrEqual(2);
        });
    });
});

describe('SmartRetryResult', function () {
    it('can be created with success state', function () {
        $result = new SmartRetryResult(
            success: true,
            attempts: [],
            message: 'Test message',
            statusCode: 200,
            responseTime: 100.5,
        );

        expect($result->isSuccess())->toBeTrue()
            ->and($result->message)->toBe('Test message')
            ->and($result->statusCode)->toBe(200)
            ->and($result->responseTime)->toBe(100.5);
    });

    it('can count attempts', function () {
        $attempts = [
            new SmartRetryAttempt(success: false, attemptNumber: 1),
            new SmartRetryAttempt(success: false, attemptNumber: 2),
            new SmartRetryAttempt(success: true, attemptNumber: 3),
        ];

        $result = new SmartRetryResult(
            success: true,
            attempts: $attempts,
        );

        expect($result->getAttemptCount())->toBe(3);
    });

    it('can get successful attempt', function () {
        $attempts = [
            new SmartRetryAttempt(success: false, attemptNumber: 1),
            new SmartRetryAttempt(success: true, attemptNumber: 2, statusCode: 200),
        ];

        $result = new SmartRetryResult(
            success: true,
            attempts: $attempts,
        );

        $successfulAttempt = $result->getSuccessfulAttempt();

        expect($successfulAttempt)->not->toBeNull()
            ->and($successfulAttempt->attemptNumber)->toBe(2)
            ->and($successfulAttempt->statusCode)->toBe(200);
    });

    it('returns null when no successful attempt', function () {
        $attempts = [
            new SmartRetryAttempt(success: false, attemptNumber: 1),
            new SmartRetryAttempt(success: false, attemptNumber: 2),
        ];

        $result = new SmartRetryResult(
            success: false,
            attempts: $attempts,
        );

        expect($result->getSuccessfulAttempt())->toBeNull();
    });
});

describe('SmartRetryAttempt', function () {
    it('can identify timeout errors', function () {
        $attempt = new SmartRetryAttempt(
            success: false,
            errorType: SmartRetryAttempt::ERROR_TIMEOUT,
        );

        expect($attempt->isTimeout())->toBeTrue()
            ->and($attempt->isConnectionRefused())->toBeFalse()
            ->and($attempt->isDnsError())->toBeFalse();
    });

    it('can identify connection refused errors', function () {
        $attempt = new SmartRetryAttempt(
            success: false,
            errorType: SmartRetryAttempt::ERROR_CONNECTION_REFUSED,
        );

        expect($attempt->isConnectionRefused())->toBeTrue()
            ->and($attempt->isTimeout())->toBeFalse();
    });

    it('can identify DNS errors', function () {
        $attempt = new SmartRetryAttempt(
            success: false,
            errorType: SmartRetryAttempt::ERROR_DNS,
        );

        expect($attempt->isDnsError())->toBeTrue()
            ->and($attempt->isTimeout())->toBeFalse();
    });

    it('can identify HTTP status errors', function () {
        $attempt = new SmartRetryAttempt(
            success: false,
            errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
            statusCode: 500,
        );

        expect($attempt->isHttpStatusError())->toBeTrue()
            ->and($attempt->statusCode)->toBe(500);
    });

    it('can convert to array', function () {
        $attempt = new SmartRetryAttempt(
            success: true,
            type: SmartRetryAttempt::TYPE_HTTP,
            method: 'HEAD',
            statusCode: 200,
            responseTime: 150.5,
            attemptNumber: 1,
        );

        $array = $attempt->toArray();

        expect($array)->toBeArray()
            ->and($array['success'])->toBeTrue()
            ->and($array['type'])->toBe('http')
            ->and($array['method'])->toBe('HEAD')
            ->and($array['status_code'])->toBe(200)
            ->and($array['response_time'])->toBe(150.5)
            ->and($array['attempt_number'])->toBe(1);
    });
});
