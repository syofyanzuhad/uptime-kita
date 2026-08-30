<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class PublicToolsService
{
    public function __construct(
        private readonly ?DomainExpirationService $domainExpirationService = null
    ) {}

    /**
     * Inspect SSL Certificate for a domain.
     *
     * @return array<string, mixed>
     */
    public function checkSsl(string $input): array
    {
        $domain = $this->cleanDomain($input);

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $start = microtime(true);
        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $client) {
            return [
                'ok' => false,
                'domain' => $domain,
                'error' => "Could not connect to {$domain} on port 443: {$errstr} ({$errno})",
            ];
        }

        $params = stream_context_get_params($client);
        fclose($client);

        if (empty($params['options']['ssl']['peer_certificate'])) {
            return [
                'ok' => false,
                'domain' => $domain,
                'error' => 'No SSL certificate presented by server.',
            ];
        }

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if (! $cert) {
            return [
                'ok' => false,
                'domain' => $domain,
                'error' => 'Unable to parse SSL certificate.',
            ];
        }

        $validFrom = isset($cert['validFrom_time_t']) ? date('Y-m-d H:i:s', $cert['validFrom_time_t']) : null;
        $validTo = isset($cert['validTo_time_t']) ? date('Y-m-d H:i:s', $cert['validTo_time_t']) : null;
        $daysRemaining = isset($cert['validTo_time_t']) ? (int) floor(($cert['validTo_time_t'] - time()) / 86400) : 0;
        $isValid = $daysRemaining > 0 && ($cert['validFrom_time_t'] <= time());

        // Extract SANs (Subject Alternative Names)
        $sans = [];
        if (! empty($cert['extensions']['subjectAltName'])) {
            $rawSans = explode(',', $cert['extensions']['subjectAltName']);
            foreach ($rawSans as $s) {
                $trimmed = trim(str_replace('DNS:', '', $s));
                if ($trimmed) {
                    $sans[] = $trimmed;
                }
            }
        }

        return [
            'ok' => true,
            'domain' => $domain,
            'is_valid' => $isValid,
            'days_remaining' => $daysRemaining,
            'issuer' => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown Issuer',
            'issuer_details' => $cert['issuer'] ?? [],
            'subject' => $cert['subject']['CN'] ?? $domain,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'sans' => $sans,
            'signature_type' => $cert['signatureTypeSN'] ?? null,
            'elapsed_ms' => round((microtime(true) - $start) * 1000, 1),
        ];
    }

    /**
     * Query DNS records for a domain.
     *
     * @return array<string, mixed>
     */
    public function lookupDns(string $input, string $type = 'ALL'): array
    {
        $domain = $this->cleanDomain($input);

        $typeMap = [
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'MX' => DNS_MX,
            'TXT' => DNS_TXT,
            'CNAME' => DNS_CNAME,
            'NS' => DNS_NS,
            'SOA' => DNS_SOA,
            'ALL' => DNS_ALL,
        ];

        $dnsType = $typeMap[strtoupper($type)] ?? DNS_ALL;

        $start = microtime(true);
        $records = @dns_get_record($domain, $dnsType);

        if ($records === false) {
            return [
                'ok' => false,
                'domain' => $domain,
                'error' => "DNS lookup failed for {$domain}.",
                'records' => [],
            ];
        }

        $formatted = [];
        foreach ($records as $r) {
            $formatted[] = [
                'host' => $r['host'] ?? $domain,
                'type' => $r['type'] ?? 'UNKNOWN',
                'ttl' => $r['ttl'] ?? 300,
                'target' => $r['ip'] ?? $r['ipv6'] ?? $r['target'] ?? $r['txt'] ?? ($r['mname'] ?? json_encode($r)),
                'pri' => $r['pri'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'domain' => $domain,
            'type' => $type,
            'count' => count($formatted),
            'records' => $formatted,
            'elapsed_ms' => round((microtime(true) - $start) * 1000, 1),
        ];
    }

    /**
     * Inspect HTTP response and security headers for a URL.
     *
     * @return array<string, mixed>
     */
    public function checkHeaders(string $input): array
    {
        $url = $this->normalizeUrl($input);
        $start = microtime(true);

        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->withHeaders([
                    'User-Agent' => 'UptimeKita-HeaderInspector/1.0',
                ])
                ->get($url);

            $headers = $response->headers();
            $statusCode = $response->status();
            $elapsedMs = round((microtime(true) - $start) * 1000, 1);

            // Audit standard security headers
            $securityHeaders = [
                'Strict-Transport-Security' => [
                    'label' => 'HSTS',
                    'present' => isset($headers['Strict-Transport-Security']) || isset($headers['strict-transport-security']),
                    'value' => $headers['Strict-Transport-Security'][0] ?? $headers['strict-transport-security'][0] ?? null,
                    'recommendation' => 'Enforces HTTPS connections to prevent man-in-the-middle attacks.',
                ],
                'Content-Security-Policy' => [
                    'label' => 'CSP',
                    'present' => isset($headers['Content-Security-Policy']) || isset($headers['content-security-policy']),
                    'value' => $headers['Content-Security-Policy'][0] ?? $headers['content-security-policy'][0] ?? null,
                    'recommendation' => 'Restricts sources of executable scripts to prevent XSS attacks.',
                ],
                'X-Frame-Options' => [
                    'label' => 'X-Frame-Options',
                    'present' => isset($headers['X-Frame-Options']) || isset($headers['x-frame-options']),
                    'value' => $headers['X-Frame-Options'][0] ?? $headers['x-frame-options'][0] ?? null,
                    'recommendation' => 'Protects against clickjacking by disallowing iframe embedding.',
                ],
                'X-Content-Type-Options' => [
                    'label' => 'X-Content-Type-Options',
                    'present' => isset($headers['X-Content-Type-Options']) || isset($headers['x-content-type-options']),
                    'value' => $headers['X-Content-Type-Options'][0] ?? $headers['x-content-type-options'][0] ?? null,
                    'recommendation' => 'Prevents MIME-sniffing attacks (should be "nosniff").',
                ],
                'Referrer-Policy' => [
                    'label' => 'Referrer-Policy',
                    'present' => isset($headers['Referrer-Policy']) || isset($headers['referrer-policy']),
                    'value' => $headers['Referrer-Policy'][0] ?? $headers['referrer-policy'][0] ?? null,
                    'recommendation' => 'Controls how much referrer information is sent with requests.',
                ],
                'Permissions-Policy' => [
                    'label' => 'Permissions-Policy',
                    'present' => isset($headers['Permissions-Policy']) || isset($headers['permissions-policy']),
                    'value' => $headers['Permissions-Policy'][0] ?? $headers['permissions-policy'][0] ?? null,
                    'recommendation' => 'Restricts browser features like camera, microphone, and geolocation.',
                ],
            ];

            // Compute security grade
            $presentCount = 0;
            foreach ($securityHeaders as $sh) {
                if ($sh['present']) {
                    $presentCount++;
                }
            }

            $score = match ($presentCount) {
                6 => 'A+',
                5 => 'A',
                4 => 'B',
                3 => 'C',
                2 => 'D',
                default => 'F',
            };

            // Flatten all headers for inspection
            $allHeaders = [];
            foreach ($headers as $k => $v) {
                $allHeaders[$k] = is_array($v) ? implode(', ', $v) : $v;
            }

            return [
                'ok' => true,
                'url' => $url,
                'status_code' => $statusCode,
                'score' => $score,
                'security_headers' => $securityHeaders,
                'all_headers' => $allHeaders,
                'elapsed_ms' => $elapsedMs,
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'url' => $url,
                'error' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Inspect Domain Registration & Expiration for a domain.
     *
     * @return array<string, mixed>
     */
    public function checkDomainExpiration(string $input): array
    {
        $domain = $this->cleanDomain($input);
        if (empty($domain)) {
            return [
                'ok' => false,
                'domain' => $input,
                'error' => 'Invalid domain name.',
            ];
        }

        $start = microtime(true);
        $service = $this->domainExpirationService ?? app(DomainExpirationService::class);
        $expirationDate = $service->lookupExpirationDate($domain);
        $elapsedMs = round((microtime(true) - $start) * 1000, 1);

        if (! $expirationDate) {
            return [
                'ok' => false,
                'domain' => $domain,
                'error' => "Could not retrieve WHOIS/RDAP expiration date for {$domain}.",
                'elapsed_ms' => $elapsedMs,
            ];
        }

        $now = now();
        $daysRemaining = (int) $now->diffInDays($expirationDate, false);
        $isExpired = $daysRemaining < 0;

        return [
            'ok' => true,
            'domain' => $domain,
            'expires_at' => $expirationDate->toIso8601String(),
            'expires_at_formatted' => $expirationDate->format('Y-m-d H:i:s T'),
            'days_remaining' => max(0, $daysRemaining),
            'is_expired' => $isExpired,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    private function cleanDomain(string $input): string
    {
        $cleaned = trim($input);
        if (str_starts_with($cleaned, 'http://') || str_starts_with($cleaned, 'https://')) {
            $parsed = parse_url($cleaned, PHP_URL_HOST);
            if ($parsed) {
                $cleaned = $parsed;
            }
        }

        return rtrim(preg_replace('/[^a-zA-Z0-9.-]/', '', $cleaned), '/');
    }

    private function normalizeUrl(string $input): string
    {
        $trimmed = trim($input);
        if (! str_starts_with($trimmed, 'http://') && ! str_starts_with($trimmed, 'https://')) {
            $trimmed = 'https://'.$trimmed;
        }

        return rtrim($trimmed, '/');
    }
}
