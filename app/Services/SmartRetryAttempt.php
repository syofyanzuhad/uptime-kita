<?php

namespace App\Services;

class SmartRetryAttempt
{
    public const TYPE_HTTP = 'http';

    public const TYPE_TCP = 'tcp';

    public const ERROR_TIMEOUT = 'timeout';

    public const ERROR_CONNECTION_REFUSED = 'connection_refused';

    public const ERROR_DNS = 'dns';

    public const ERROR_SSL = 'ssl';

    public const ERROR_HTTP_STATUS = 'http_status';

    public const ERROR_STRING_NOT_FOUND = 'string_not_found';

    public const ERROR_UNKNOWN = 'unknown';

    public function __construct(
        public bool $success,
        public string $type = self::TYPE_HTTP,
        public ?string $method = null,
        public ?int $statusCode = null,
        public ?float $responseTime = null,
        public ?string $errorType = null,
        public ?string $errorMessage = null,
        public int $attemptNumber = 1,
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isTimeout(): bool
    {
        return $this->errorType === self::ERROR_TIMEOUT;
    }

    public function isConnectionRefused(): bool
    {
        return $this->errorType === self::ERROR_CONNECTION_REFUSED;
    }

    public function isDnsError(): bool
    {
        return $this->errorType === self::ERROR_DNS;
    }

    public function isSslError(): bool
    {
        return $this->errorType === self::ERROR_SSL;
    }

    public function isHttpStatusError(): bool
    {
        return $this->errorType === self::ERROR_HTTP_STATUS;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'type' => $this->type,
            'method' => $this->method,
            'status_code' => $this->statusCode,
            'response_time' => $this->responseTime,
            'error_type' => $this->errorType,
            'error_message' => $this->errorMessage,
            'attempt_number' => $this->attemptNumber,
        ];
    }
}
