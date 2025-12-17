<?php

use App\Models\Monitor;
use App\Models\MonitorIncident;
use App\Services\AlertPatternService;

beforeEach(function () {
    $this->service = new AlertPatternService;
});

describe('AlertPatternService', function () {

    describe('isFibonacciNumber', function () {
        it('correctly identifies Fibonacci numbers', function () {
            $fibonacciNumbers = [1, 2, 3, 5, 8, 13, 21, 34, 55, 89];

            foreach ($fibonacciNumbers as $n) {
                expect($this->service->isFibonacciNumber($n))->toBeTrue(
                    "Expected {$n} to be identified as Fibonacci"
                );
            }
        });

        it('correctly rejects non-Fibonacci numbers', function () {
            $nonFibonacciNumbers = [4, 6, 7, 9, 10, 11, 12, 14, 15, 16, 17, 18, 19, 20];

            foreach ($nonFibonacciNumbers as $n) {
                expect($this->service->isFibonacciNumber($n))->toBeFalse(
                    "Expected {$n} to NOT be identified as Fibonacci"
                );
            }
        });

        it('returns false for zero and negative numbers', function () {
            expect($this->service->isFibonacciNumber(0))->toBeFalse();
            expect($this->service->isFibonacciNumber(-1))->toBeFalse();
            expect($this->service->isFibonacciNumber(-5))->toBeFalse();
        });
    });

    describe('shouldSendDownAlert', function () {
        it('always returns true for PATTERN_EVERY', function () {
            $monitor = Monitor::factory()->create([
                'notification_settings' => ['alert_pattern' => AlertPatternService::PATTERN_EVERY],
                'uptime_check_times_failed_in_a_row' => 4,
            ]);

            expect($this->service->shouldSendDownAlert($monitor))->toBeTrue();
        });

        it('returns true only for Fibonacci counts with PATTERN_FIBONACCI', function () {
            $monitor = Monitor::factory()->create([
                'notification_settings' => ['alert_pattern' => AlertPatternService::PATTERN_FIBONACCI],
            ]);

            // Test Fibonacci numbers - should send
            foreach ([1, 2, 3, 5, 8, 13, 21] as $count) {
                $monitor->uptime_check_times_failed_in_a_row = $count;
                expect($this->service->shouldSendDownAlert($monitor))->toBeTrue(
                    "Expected alert at failure count {$count}"
                );
            }

            // Test non-Fibonacci numbers - should not send
            foreach ([4, 6, 7, 9, 10, 11, 12] as $count) {
                $monitor->uptime_check_times_failed_in_a_row = $count;
                expect($this->service->shouldSendDownAlert($monitor))->toBeFalse(
                    "Expected NO alert at failure count {$count}"
                );
            }
        });

        it('defaults to PATTERN_EVERY when no setting exists', function () {
            $monitor = Monitor::factory()->create([
                'notification_settings' => null,
                'uptime_check_times_failed_in_a_row' => 4,
            ]);

            expect($this->service->shouldSendDownAlert($monitor))->toBeTrue();
        });

        it('defaults to PATTERN_EVERY when notification_settings is empty array', function () {
            $monitor = Monitor::factory()->create([
                'notification_settings' => [],
                'uptime_check_times_failed_in_a_row' => 4,
            ]);

            expect($this->service->shouldSendDownAlert($monitor))->toBeTrue();
        });
    });

    describe('shouldSendRecoveryAlert', function () {
        it('returns true only if DOWN alert was sent', function () {
            $monitor = Monitor::factory()->create();

            $incidentWithAlert = MonitorIncident::factory()->alertSent()->create([
                'monitor_id' => $monitor->id,
            ]);

            $incidentWithoutAlert = MonitorIncident::factory()->noAlertSent()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($this->service->shouldSendRecoveryAlert($monitor, $incidentWithAlert))->toBeTrue();
            expect($this->service->shouldSendRecoveryAlert($monitor, $incidentWithoutAlert))->toBeFalse();
        });

        it('returns false when incident is null', function () {
            $monitor = Monitor::factory()->create();

            expect($this->service->shouldSendRecoveryAlert($monitor, null))->toBeFalse();
        });
    });

    describe('getNextAlertAt', function () {
        it('returns the next Fibonacci number', function () {
            expect($this->service->getNextAlertAt(0))->toBe(1);
            expect($this->service->getNextAlertAt(1))->toBe(2);
            expect($this->service->getNextAlertAt(2))->toBe(3);
            expect($this->service->getNextAlertAt(3))->toBe(5);
            expect($this->service->getNextAlertAt(4))->toBe(5);
            expect($this->service->getNextAlertAt(5))->toBe(8);
            expect($this->service->getNextAlertAt(6))->toBe(8);
            expect($this->service->getNextAlertAt(7))->toBe(8);
            expect($this->service->getNextAlertAt(8))->toBe(13);
        });
    });

    describe('getAlertPattern', function () {
        it('returns fibonacci when configured', function () {
            $monitor = Monitor::factory()->create([
                'notification_settings' => ['alert_pattern' => 'fibonacci'],
            ]);

            expect($this->service->getAlertPattern($monitor))->toBe(AlertPatternService::PATTERN_FIBONACCI);
        });

        it('returns every as default', function () {
            $monitor = Monitor::factory()->create([
                'notification_settings' => null,
            ]);

            expect($this->service->getAlertPattern($monitor))->toBe(AlertPatternService::PATTERN_EVERY);
        });
    });

    describe('getPatternOptions', function () {
        it('returns available patterns', function () {
            $options = AlertPatternService::getPatternOptions();

            expect($options)->toHaveKey('every');
            expect($options)->toHaveKey('fibonacci');
            expect($options['every'])->toBe('Every failure');
            expect($options['fibonacci'])->toContain('Fibonacci');
        });
    });
});
