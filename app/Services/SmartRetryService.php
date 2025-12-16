<?php

namespace App\Services;

use App\Models\Monitor;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Log;

class SmartRetryService
{
    public const SENSITIVITY_PRESETS = [
        'low' => [
            'confirmation_delay' => 60,
            'retries' => 5,
            'backoff_multiplier' => 2,
            'initial_delay_ms' => 200,
        ],
        'medium' => [
            'confirmation_delay' => 30,
            'retries' => 3,
            'backoff_multiplier' => 2,
            'initial_delay_ms' => 100,
        ],
        'high' => [
            'confirmation_delay' => 15,
            'retries' => 2,
            'backoff_multiplier' => 1.5,
            'initial_delay_ms' => 50,
        ],
    ];

    protected Client $client;

    protected int $timeout;

    public function __construct()
    {
        $this->timeout = config('uptime-monitor.confirmation_check.timeout_seconds', 5);
        $this->client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'http_errors' => false,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => false,
                'protocols' => ['http', 'https'],
            ],
        ]);
    }

    public static function getPreset(string $sensitivity): array
    {
        return self::SENSITIVITY_PRESETS[$sensitivity] ?? self::SENSITIVITY_PRESETS['medium'];
    }

    public function performSmartCheck(Monitor $monitor, array $options = []): SmartRetryResult
    {
        $retries = $options['retries'] ?? 3;
        $initialDelay = $options['initial_delay_ms'] ?? 100;
        $backoffMultiplier = $options['backoff_multiplier'] ?? 2;

        $attempts = [];
        $currentDelay = $initialDelay;
        $attemptNumber = 1;

        for ($i = 0; $i < $retries; $i++) {
            // Try HEAD first (lighter request)
            $result = $this->tryHttpRequest($monitor, 'HEAD', $attemptNumber);
            $attempts[] = $result;
            $attemptNumber++;

            if ($result->isSuccess()) {
                return $this->buildSuccessResult($attempts, $result);
            }

            // If HEAD times out, try GET (some servers don't respond to HEAD properly)
            if ($result->isTimeout() && $i < $retries - 1) {
                usleep($currentDelay * 1000);

                $result = $this->tryHttpRequest($monitor, 'GET', $attemptNumber);
                $attempts[] = $result;
                $attemptNumber++;

                if ($result->isSuccess()) {
                    return $this->buildSuccessResult($attempts, $result);
                }
            }

            // Exponential backoff before next retry
            if ($i < $retries - 1) {
                usleep($currentDelay * 1000);
                $currentDelay = (int) ($currentDelay * $backoffMultiplier);
            }
        }

        // Final attempt: TCP ping (port check)
        if ($this->canPerformTcpPing($monitor)) {
            $tcpResult = $this->tryTcpPing($monitor, $attemptNumber);
            $attempts[] = $tcpResult;

            if ($tcpResult->isSuccess()) {
                // Server responding to TCP but HTTP failing - likely app issue, not network
                return new SmartRetryResult(
                    success: false,
                    attempts: $attempts,
                    message: 'HTTP failure but TCP responsive - likely application issue',
                );
            }
        }

        $lastAttempt = $attempts[array_key_last($attempts)];

        return new SmartRetryResult(
            success: false,
            attempts: $attempts,
            message: $lastAttempt->errorMessage ?? 'All retry attempts failed',
            statusCode: $lastAttempt->statusCode,
        );
    }

    protected function tryHttpRequest(Monitor $monitor, string $method, int $attemptNumber): SmartRetryAttempt
    {
        $url = (string) $monitor->url;
        $responseTime = null;

        $options = [
            'on_stats' => function (TransferStats $stats) use (&$responseTime) {
                $responseTime = $stats->getTransferTime() * 1000; // Convert to ms
            },
        ];

        // Add custom headers if configured
        if ($monitor->uptime_check_additional_headers) {
            $headers = is_array($monitor->uptime_check_additional_headers)
                ? $monitor->uptime_check_additional_headers
                : json_decode($monitor->uptime_check_additional_headers, true);

            if ($headers) {
                $options['headers'] = $headers;
            }
        }

        // Add payload if configured (only for non-HEAD requests)
        if ($method !== 'HEAD' && $monitor->uptime_check_payload) {
            $options['body'] = $monitor->uptime_check_payload;
        }

        $startTime = microtime(true);

        try {
            $response = $this->client->request($method, $url, $options);
            $statusCode = $response->getStatusCode();

            // Calculate response time if not captured by stats
            if ($responseTime === null) {
                $responseTime = (microtime(true) - $startTime) * 1000;
            }

            // Check if status code is acceptable
            $expectedStatusCode = $monitor->expected_status_code ?? 200;
            $additionalStatusCodes = config('uptime-monitor.uptime_check.additional_status_codes', []);
            $acceptableStatusCodes = array_merge([$expectedStatusCode], $additionalStatusCodes, [200, 201, 204, 301, 302]);

            if (! in_array($statusCode, $acceptableStatusCodes)) {
                return new SmartRetryAttempt(
                    success: false,
                    type: SmartRetryAttempt::TYPE_HTTP,
                    method: $method,
                    statusCode: $statusCode,
                    responseTime: $responseTime,
                    errorType: SmartRetryAttempt::ERROR_HTTP_STATUS,
                    errorMessage: "Unexpected status code: {$statusCode}",
                    attemptNumber: $attemptNumber,
                );
            }

            // Check for look_for_string if configured (only for GET requests)
            if ($method === 'GET' && $monitor->look_for_string) {
                $body = (string) $response->getBody();
                if (stripos($body, $monitor->look_for_string) === false) {
                    return new SmartRetryAttempt(
                        success: false,
                        type: SmartRetryAttempt::TYPE_HTTP,
                        method: $method,
                        statusCode: $statusCode,
                        responseTime: $responseTime,
                        errorType: SmartRetryAttempt::ERROR_STRING_NOT_FOUND,
                        errorMessage: "String not found: {$monitor->look_for_string}",
                        attemptNumber: $attemptNumber,
                    );
                }
            }

            return new SmartRetryAttempt(
                success: true,
                type: SmartRetryAttempt::TYPE_HTTP,
                method: $method,
                statusCode: $statusCode,
                responseTime: $responseTime,
                attemptNumber: $attemptNumber,
            );

        } catch (ConnectException $e) {
            $errorType = $this->classifyConnectionError($e);

            return new SmartRetryAttempt(
                success: false,
                type: SmartRetryAttempt::TYPE_HTTP,
                method: $method,
                responseTime: (microtime(true) - $startTime) * 1000,
                errorType: $errorType,
                errorMessage: $e->getMessage(),
                attemptNumber: $attemptNumber,
            );

        } catch (RequestException $e) {
            return new SmartRetryAttempt(
                success: false,
                type: SmartRetryAttempt::TYPE_HTTP,
                method: $method,
                statusCode: $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                responseTime: (microtime(true) - $startTime) * 1000,
                errorType: SmartRetryAttempt::ERROR_UNKNOWN,
                errorMessage: $e->getMessage(),
                attemptNumber: $attemptNumber,
            );

        } catch (\Exception $e) {
            Log::error('SmartRetryService: Unexpected error', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return new SmartRetryAttempt(
                success: false,
                type: SmartRetryAttempt::TYPE_HTTP,
                method: $method,
                responseTime: (microtime(true) - $startTime) * 1000,
                errorType: SmartRetryAttempt::ERROR_UNKNOWN,
                errorMessage: $e->getMessage(),
                attemptNumber: $attemptNumber,
            );
        }
    }

    protected function classifyConnectionError(ConnectException $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return SmartRetryAttempt::ERROR_TIMEOUT;
        }

        if (str_contains($message, 'connection refused')) {
            return SmartRetryAttempt::ERROR_CONNECTION_REFUSED;
        }

        if (str_contains($message, 'could not resolve') || str_contains($message, 'name or service not known')) {
            return SmartRetryAttempt::ERROR_DNS;
        }

        if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
            return SmartRetryAttempt::ERROR_SSL;
        }

        return SmartRetryAttempt::ERROR_UNKNOWN;
    }

    protected function canPerformTcpPing(Monitor $monitor): bool
    {
        $url = $monitor->url;

        return $url !== null && $url->getHost() !== '';
    }

    protected function tryTcpPing(Monitor $monitor, int $attemptNumber): SmartRetryAttempt
    {
        $url = $monitor->url;
        $host = $url->getHost();
        $port = $url->getPort() ?? ($url->getScheme() === 'https' ? 443 : 80);

        $startTime = microtime(true);

        $socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);

        $responseTime = (microtime(true) - $startTime) * 1000;

        if ($socket) {
            fclose($socket);

            return new SmartRetryAttempt(
                success: true,
                type: SmartRetryAttempt::TYPE_TCP,
                responseTime: $responseTime,
                attemptNumber: $attemptNumber,
            );
        }

        $errorType = match ($errno) {
            110, 111 => SmartRetryAttempt::ERROR_CONNECTION_REFUSED,
            default => SmartRetryAttempt::ERROR_TIMEOUT,
        };

        return new SmartRetryAttempt(
            success: false,
            type: SmartRetryAttempt::TYPE_TCP,
            responseTime: $responseTime,
            errorType: $errorType,
            errorMessage: $errstr,
            attemptNumber: $attemptNumber,
        );
    }

    protected function buildSuccessResult(array $attempts, SmartRetryAttempt $successfulAttempt): SmartRetryResult
    {
        return new SmartRetryResult(
            success: true,
            attempts: $attempts,
            statusCode: $successfulAttempt->statusCode,
            responseTime: $successfulAttempt->responseTime,
        );
    }
}
