<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DomainCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $input = trim($request->input('url'));

        // Normalize: add https:// if no scheme
        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.$input;
        }

        // Validate URL after normalization
        if (! filter_var($input, FILTER_VALIDATE_URL)) {
            return response()->json(['message' => 'Invalid URL. Example: example.com or https://example.com'], 422);
        }

        $host = parse_url($input, PHP_URL_HOST);
        if (! $host || ! preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $host)) {
            return response()->json(['message' => 'Invalid domain.'], 422);
        }

        // SSRF guard: block private / loopback
        $blocked = ['127.', '10.', '192.168.', '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.', '169.254.', '::1', 'fc00:', 'fe80:'];
        foreach ($blocked as $prefix) {
            if (str_starts_with($host, $prefix) || str_starts_with($input, 'http://'.$prefix) || str_starts_with($input, 'https://'.$prefix)) {
                return response()->json(['message' => 'Private/local addresses not allowed.'], 422);
            }
        }
        // Resolve and block if resolves to private IP
        $ip = gethostbyname($host);
        if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return response()->json(['message' => 'Private/local addresses not allowed.'], 422);
        }

        $start = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'UptimeKita-Checker/1.0'])
                ->withOptions(['allow_redirects' => false])
                ->get($input);
            $elapsed = (int) ((microtime(true) - $start) * 1000);
            $status = $response->status();

            return response()->json([
                'url' => $input,
                'host' => $host,
                'status_code' => $status,
                'ok' => $status >= 200 && $status < 400,
                'response_time_ms' => $elapsed,
                'headers' => [
                    'content-type' => $response->header('Content-Type'),
                    'server' => $response->header('Server'),
                ],
            ]);
        } catch (\Throwable $e) {
            $elapsed = (int) ((microtime(true) - $start) * 1000);

            return response()->json([
                'url' => $input,
                'host' => $host,
                'status_code' => null,
                'ok' => false,
                'response_time_ms' => $elapsed,
                'error' => str_contains($e->getMessage(), 'cURL error') ? 'Could not reach host.' : $e->getMessage(),
            ]);
        }
    }
}
