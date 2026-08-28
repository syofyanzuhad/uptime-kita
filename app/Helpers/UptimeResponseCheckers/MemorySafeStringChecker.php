<?php

namespace App\Helpers\UptimeResponseCheckers;

use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Helpers\UptimeResponseCheckers\UptimeResponseChecker;
use Spatie\UptimeMonitor\Models\Monitor;

class MemorySafeStringChecker implements UptimeResponseChecker
{
    /**
     * Max bytes to read from the response stream (1MB).
     * This prevents OOM errors when a user monitors a large file.
     */
    protected int $maxBytesToRead = 1048576;

    public function isValidResponse(ResponseInterface $response, Monitor $monitor): bool
    {
        if (empty($monitor->look_for_string)) {
            // Even if we don't look for a string, we should close the stream
            // to free up resources immediately if it's open
            if ($response->getBody()->isSeekable()) {
                $response->getBody()->close();
            }

            return true;
        }

        $body = $response->getBody();
        $content = '';

        // Read stream up to 1MB to prevent memory exhaustion
        if ($body->isReadable()) {
            $content = $body->read($this->maxBytesToRead);
            $body->close(); // Close stream early since we got what we needed
        } else {
            $content = (string) $body;
        }

        return Str::contains($content, $monitor->look_for_string);
    }

    public function getFailureReason(ResponseInterface $response, Monitor $monitor): string
    {
        return "String `{$monitor->look_for_string}` was not found on the response.";
    }
}
