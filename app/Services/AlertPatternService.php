<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitorIncident;

class AlertPatternService
{
    // Alert pattern types
    public const PATTERN_EVERY = 'every';

    public const PATTERN_FIBONACCI = 'fibonacci';

    // Pre-computed Fibonacci sequence up to reasonable limit
    private const FIBONACCI_SEQUENCE = [1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 144, 233, 377, 610, 987];

    /**
     * Determine if a DOWN alert should be sent based on the monitor's alert pattern settings.
     */
    public function shouldSendDownAlert(Monitor $monitor): bool
    {
        $pattern = $this->getAlertPattern($monitor);
        $failureCount = $monitor->uptime_check_times_failed_in_a_row;

        return match ($pattern) {
            self::PATTERN_FIBONACCI => $this->isFibonacciNumber($failureCount),
            default => true, // PATTERN_EVERY - always send
        };
    }

    /**
     * Determine if a recovery alert should be sent.
     * Only send if a DOWN alert was previously sent for this incident.
     */
    public function shouldSendRecoveryAlert(Monitor $monitor, ?MonitorIncident $incident): bool
    {
        if (! $incident) {
            return false;
        }

        return $incident->down_alert_sent;
    }

    /**
     * Get the alert pattern for a monitor from its notification settings.
     */
    public function getAlertPattern(Monitor $monitor): string
    {
        $settings = $monitor->notification_settings ?? [];

        return $settings['alert_pattern'] ?? self::PATTERN_EVERY;
    }

    /**
     * Check if a number is in the Fibonacci sequence.
     */
    public function isFibonacciNumber(int $n): bool
    {
        if ($n <= 0) {
            return false;
        }

        // Use pre-computed sequence for efficiency (last element is 987)
        $maxPrecomputed = self::FIBONACCI_SEQUENCE[count(self::FIBONACCI_SEQUENCE) - 1];
        if ($n <= $maxPrecomputed) {
            return in_array($n, self::FIBONACCI_SEQUENCE, true);
        }

        // For numbers beyond our pre-computed sequence, use mathematical check
        // A number is Fibonacci if one of (5*n^2 + 4) or (5*n^2 - 4) is a perfect square
        return $this->isPerfectSquare(5 * $n * $n + 4)
            || $this->isPerfectSquare(5 * $n * $n - 4);
    }

    /**
     * Check if a number is a perfect square.
     */
    private function isPerfectSquare(int $n): bool
    {
        $sqrt = (int) sqrt($n);

        return $sqrt * $sqrt === $n;
    }

    /**
     * Get the next Fibonacci number that will trigger an alert.
     * Useful for UI display and logging.
     */
    public function getNextAlertAt(int $currentFailures): int
    {
        foreach (self::FIBONACCI_SEQUENCE as $fib) {
            if ($fib > $currentFailures) {
                return $fib;
            }
        }

        // Beyond pre-computed, return approximate
        return $currentFailures + 1;
    }

    /**
     * Get available alert pattern options for UI.
     */
    public static function getPatternOptions(): array
    {
        return [
            self::PATTERN_EVERY => 'Every failure',
            self::PATTERN_FIBONACCI => 'Fibonacci (1, 2, 3, 5, 8, 13...)',
        ];
    }
}
