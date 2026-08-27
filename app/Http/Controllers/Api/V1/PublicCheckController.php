<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PublicCheckController extends Controller
{
    /**
     * Handle the incoming request for instant uptime and health check.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'check_ssl' => ['sometimes', 'boolean'],
            'timeout' => ['sometimes', 'integer', 'min:1', 'max:15'],
        ]);

        $input = trim($validated['url']);
        $checkSsl = $request->boolean('check_ssl', true);
        $timeout = (int) ($validated['timeout'] ?? 10);

        // Normalize: prefix https:// if no scheme provided
        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.$input;
        }

        // Validate URL syntax
        if (! filter_var($input, FILTER_VALIDATE_URL)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => 'Invalid URL provided. Example: example.com or https://example.com',
            ], 422);
        }

        $host = parse_url($input, PHP_URL_HOST);
        $scheme = parse_url($input, PHP_URL_SCHEME);

        if (! $host || ! preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $host)) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => 'Invalid domain name or hostname.',
            ], 422);
        }

        // SSRF protection: block private/loopback IP prefixes and hostnames
        $blockedPrefixes = [
            '127.', '10.', '192.168.',
            '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.',
            '169.254.', '0.0.0.0', 'localhost', '::1', 'fc00:', 'fe80:',
        ];

        foreach ($blockedPrefixes as $prefix) {
            if (str_starts_with($host, $prefix) || str_starts_with($input, 'http://'.$prefix) || str_starts_with($input, 'https://'.$prefix)) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'message' => 'Private and local network addresses are not allowed.',
                ], 422);
            }
        }

        // DNS resolution check: ensure target host resolves to a public IP
        $resolvedIp = gethostbyname($host);
        if ($resolvedIp !== $host && filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'message' => 'Target resolves to a private or reserved IP address.',
            ], 422);
        }

        $start = microtime(true);
        $sslInfo = null;

        // SSL Certificate details inspection (if HTTPS and enabled)
        if ($scheme === 'https' && $checkSsl) {
            $sslInfo = $this->extractSslInfo($host);
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'UptimeKita-Checker/1.0 (+https://uptime.syofyanzuhad.dev)',
                    'Accept' => '*/*',
                ])
                ->withOptions(['allow_redirects' => false])
                ->get($input);

            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();
            $isUp = $statusCode >= 200 && $statusCode < 400;

            return response()->json([
                'ok' => $isUp,
                'status' => $isUp ? 'up' : 'down',
                'status_code' => $statusCode,
                'response_time_ms' => $elapsedMs,
                'url' => $input,
                'host' => $host,
                'ip' => $resolvedIp !== $host ? $resolvedIp : null,
                'ssl' => $sslInfo,
                'headers' => [
                    'content_type' => $response->header('Content-Type'),
                    'server' => $response->header('Server'),
                ],
                'checked_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $elapsedMs = (int) round((microtime(true) - $start) * 1000);

            return response()->json([
                'ok' => false,
                'status' => 'down',
                'status_code' => null,
                'response_time_ms' => $elapsedMs,
                'url' => $input,
                'host' => $host,
                'ip' => $resolvedIp !== $host ? $resolvedIp : null,
                'ssl' => $sslInfo,
                'error' => str_contains($e->getMessage(), 'cURL error') ? 'Could not connect to host (connection timeout or unreachable).' : $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Extract SSL Certificate info for a given hostname.
     */
    protected function extractSslInfo(string $host): ?array
    {
        try {
            $streamContext = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                5,
                STREAM_CLIENT_CONNECT,
                $streamContext
            );

            if (! $client) {
                return null;
            }

            $params = stream_context_get_params($client);
            fclose($client);

            if (empty($params['options']['ssl']['peer_certificate'])) {
                return null;
            }

            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            if (! $cert) {
                return null;
            }

            $validFrom = isset($cert['validFrom_time_t']) ? Carbon::createFromTimestamp($cert['validFrom_time_t']) : null;
            $validTo = isset($cert['validTo_time_t']) ? Carbon::createFromTimestamp($cert['validTo_time_t']) : null;
            $now = Carbon::now();

            $isValid = $validTo && $validTo->isFuture() && (! $validFrom || $validFrom->isPast());
            $daysRemaining = $validTo ? (int) $now->diffInDays($validTo, false) : null;

            return [
                'valid' => $isValid,
                'issuer' => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
                'subject' => $cert['subject']['CN'] ?? $host,
                'valid_from' => $validFrom?->toIso8601String(),
                'valid_to' => $validTo?->toIso8601String(),
                'days_remaining' => max(0, $daysRemaining ?? 0),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
