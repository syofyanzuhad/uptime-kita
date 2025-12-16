<?php

namespace App\Services;

class SmartRetryResult
{
    public function __construct(
        public bool $success,
        public array $attempts = [],
        public ?string $message = null,
        public ?int $statusCode = null,
        public ?float $responseTime = null,
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getAttemptCount(): int
    {
        return count($this->attempts);
    }

    public function getLastAttempt(): ?SmartRetryAttempt
    {
        return $this->attempts[array_key_last($this->attempts)] ?? null;
    }

    public function getSuccessfulAttempt(): ?SmartRetryAttempt
    {
        foreach ($this->attempts as $attempt) {
            if ($attempt->success) {
                return $attempt;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'status_code' => $this->statusCode,
            'response_time' => $this->responseTime,
            'attempt_count' => $this->getAttemptCount(),
            'attempts' => array_map(fn ($a) => $a->toArray(), $this->attempts),
        ];
    }
}
