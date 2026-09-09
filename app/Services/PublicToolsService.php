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
     * Curated list of global public DNS resolvers across regions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGlobalDnsResolvers(): array
    {
        return [
            [
                'id' => 'us_google',
                'name' => 'Google DNS',
                'ip' => '8.8.8.8',
                'location' => 'Mountain View, US',
                'country' => 'United States',
                'country_code' => 'US',
                'flag' => '🇺🇸',
                'region' => 'North America',
                'lat' => 37.42,
                'lng' => -122.08,
            ],
            [
                'id' => 'us_cf',
                'name' => 'Cloudflare DNS',
                'ip' => '1.1.1.1',
                'location' => 'San Francisco, US',
                'country' => 'United States',
                'country_code' => 'US',
                'flag' => '🇺🇸',
                'region' => 'North America',
                'lat' => 37.77,
                'lng' => -122.41,
            ],
            [
                'id' => 'us_opendns',
                'name' => 'Cisco OpenDNS',
                'ip' => '208.67.222.222',
                'location' => 'Dallas, US',
                'country' => 'United States',
                'country_code' => 'US',
                'flag' => '🇺🇸',
                'region' => 'North America',
                'lat' => 32.77,
                'lng' => -96.79,
            ],
            [
                'id' => 'de_dns_watch',
                'name' => 'DNS.WATCH',
                'ip' => '84.200.69.80',
                'location' => 'Frankfurt, DE',
                'country' => 'Germany',
                'country_code' => 'DE',
                'flag' => '🇩🇪',
                'region' => 'Europe',
                'lat' => 50.11,
                'lng' => 8.68,
            ],
            [
                'id' => 'ch_quad9',
                'name' => 'Quad9',
                'ip' => '9.9.9.9',
                'location' => 'Zurich, CH',
                'country' => 'Switzerland',
                'country_code' => 'CH',
                'flag' => '🇨🇭',
                'region' => 'Europe',
                'lat' => 47.37,
                'lng' => 8.54,
            ],
            [
                'id' => 'gb_cleanbrowsing',
                'name' => 'CleanBrowsing',
                'ip' => '185.228.168.9',
                'location' => 'London, UK',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
                'flag' => '🇬🇧',
                'region' => 'Europe',
                'lat' => 51.50,
                'lng' => -0.12,
            ],
            [
                'id' => 'sg_cf',
                'name' => 'Cloudflare APNIC',
                'ip' => '1.0.0.1',
                'location' => 'Singapore, SG',
                'country' => 'Singapore',
                'country_code' => 'SG',
                'flag' => '🇸🇬',
                'region' => 'Asia-Pacific',
                'lat' => 1.35,
                'lng' => 103.81,
            ],
            [
                'id' => 'cn_alibaba',
                'name' => 'Alibaba AliDNS',
                'ip' => '223.5.5.5',
                'location' => 'Hangzhou, CN',
                'country' => 'China',
                'country_code' => 'CN',
                'flag' => '🇨🇳',
                'region' => 'Asia-Pacific',
                'lat' => 30.27,
                'lng' => 120.15,
            ],
            [
                'id' => 'jp_iij',
                'name' => 'IIJ Public DNS',
                'ip' => '210.130.1.1',
                'location' => 'Tokyo, JP',
                'country' => 'Japan',
                'country_code' => 'JP',
                'flag' => '🇯🇵',
                'region' => 'Asia-Pacific',
                'lat' => 35.67,
                'lng' => 139.65,
            ],
            [
                'id' => 'au_cf',
                'name' => 'Cloudflare Sydney',
                'ip' => '1.1.1.2',
                'location' => 'Sydney, AU',
                'country' => 'Australia',
                'country_code' => 'AU',
                'flag' => '🇦🇺',
                'region' => 'Oceania',
                'lat' => -33.86,
                'lng' => 151.20,
            ],
            [
                'id' => 'br_adguard',
                'name' => 'AdGuard Americas',
                'ip' => '94.140.14.14',
                'location' => 'Sao Paulo, BR',
                'country' => 'Brazil',
                'country_code' => 'BR',
                'flag' => '🇧🇷',
                'region' => 'South America',
                'lat' => -23.55,
                'lng' => -46.63,
            ],
            [
                'id' => 'za_quad9',
                'name' => 'Quad9 Johannesburg',
                'ip' => '149.112.112.112',
                'location' => 'Johannesburg, ZA',
                'country' => 'South Africa',
                'country_code' => 'ZA',
                'flag' => '🇿🇦',
                'region' => 'Africa',
                'lat' => -26.20,
                'lng' => 28.04,
            ],
        ];
    }

    /**
     * Query DNS records for a domain with optional custom server or global multi-region check.
     *
     * @return array<string, mixed>
     */
    public function lookupDns(string $input, string $type = 'ALL', ?string $server = null, bool $checkGlobal = false): array
    {
        $domain = $this->cleanDomain($input);
        $type = strtoupper($type ?: 'ALL');

        // If a specific server is specified, query it directly via raw UDP
        if (! empty($server)) {
            $rawResult = $this->queryDnsServerRaw($domain, $server, $type, 2.5);
            $rawResult['domain'] = $domain;
            $rawResult['type'] = $type;
            $rawResult['server'] = $server;

            return $rawResult;
        }

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

        $dnsType = $typeMap[$type] ?? DNS_ALL;
        $start = microtime(true);
        $records = @dns_get_record($domain, $dnsType);

        if ($records === false) {
            $localResult = [
                'ok' => false,
                'domain' => $domain,
                'type' => $type,
                'error' => "DNS lookup failed for {$domain}.",
                'records' => [],
                'count' => 0,
                'elapsed_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        } else {
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

            $localResult = [
                'ok' => true,
                'domain' => $domain,
                'type' => $type,
                'count' => count($formatted),
                'records' => $formatted,
                'elapsed_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        }

        if ($checkGlobal) {
            $globalResolvers = $this->getGlobalDnsResolvers();
            $globalResults = $this->parallelDnsQuery($domain, $globalResolvers, $type === 'ALL' ? 'A' : $type, 1.8);
            $localResult['global'] = $globalResults;
        }

        return $localResult;
    }

    /**
     * Query multiple DNS servers in parallel using non-blocking UDP sockets.
     *
     * @param  array<int, array<string, mixed>>  $resolvers
     * @return array<string, mixed>
     */
    public function parallelDnsQuery(string $domain, array $resolvers, string $type = 'A', float $timeout = 1.8): array
    {
        $types = [
            'A' => 1, 'NS' => 2, 'CNAME' => 5, 'SOA' => 6, 'MX' => 15, 'TXT' => 16, 'AAAA' => 28, 'ALL' => 255,
        ];
        $typeId = $types[strtoupper($type)] ?? 1;

        $qname = '';
        foreach (explode('.', trim($domain, '.')) as $part) {
            $qname .= chr(strlen($part)).$part;
        }
        $qname .= "\0";
        $question = $qname.pack('n2', $typeId, 1);

        $sockets = [];
        $startTimes = [];

        foreach ($resolvers as $resolver) {
            $id = $resolver['id'];
            $ip = $resolver['ip'];
            $packetId = rand(1, 65535);
            $header = pack('n6', $packetId, 0x0100, 1, 0, 0, 0);
            $packet = $header.$question;

            $sock = @stream_socket_client("udp://{$ip}:53", $errno, $errstr, $timeout, STREAM_CLIENT_ASYNC_CONNECT);
            if ($sock) {
                stream_set_blocking($sock, false);
                @fwrite($sock, $packet);
                $sockets[$id] = $sock;
                $startTimes[$id] = microtime(true);
            }
        }

        $rawResponses = [];
        $deadline = microtime(true) + $timeout;

        while (count($sockets) > 0 && microtime(true) < $deadline) {
            $read = array_values($sockets);
            $write = null;
            $except = null;
            $timeRemaining = max(0.01, $deadline - microtime(true));
            $sec = (int) floor($timeRemaining);
            $usec = (int) (($timeRemaining - $sec) * 1000000);

            if (@stream_select($read, $write, $except, $sec, $usec) > 0) {
                foreach ($read as $rSock) {
                    $key = array_search($rSock, $sockets, true);
                    $resp = @fread($rSock, 1024);
                    $elapsed = round((microtime(true) - ($startTimes[$key] ?? microtime(true))) * 1000, 1);
                    @fclose($rSock);
                    unset($sockets[$key]);

                    $rawResponses[$key] = [
                        'resp' => $resp,
                        'elapsed_ms' => $elapsed,
                    ];
                }
            }
        }

        foreach ($sockets as $key => $rSock) {
            @fclose($rSock);
            $rawResponses[$key] = [
                'resp' => null,
                'elapsed_ms' => round($timeout * 1000, 1),
            ];
        }

        $items = [];
        $successful = 0;
        $answerSignatures = [];

        foreach ($resolvers as $resolver) {
            $id = $resolver['id'];
            $entry = $rawResponses[$id] ?? null;

            if ($entry && $entry['resp'] && strlen($entry['resp']) >= 12) {
                $parsed = $this->parseDnsResponsePacket($entry['resp'], $domain, $types, $entry['elapsed_ms']);
                if ($parsed['ok']) {
                    $successful++;
                    $values = array_map(fn ($r) => $r['target'], $parsed['records']);
                    sort($values);
                    $sig = implode(', ', $values);
                    if ($sig) {
                        $answerSignatures[$sig] = ($answerSignatures[$sig] ?? 0) + 1;
                    }

                    $items[] = array_merge($resolver, [
                        'ok' => true,
                        'elapsed_ms' => $parsed['elapsed_ms'],
                        'records' => $parsed['records'],
                        'answers' => $values,
                    ]);

                    continue;
                }

                $items[] = array_merge($resolver, [
                    'ok' => false,
                    'elapsed_ms' => $parsed['elapsed_ms'],
                    'error' => $parsed['error'] ?? 'Lookup error',
                    'records' => [],
                    'answers' => [],
                ]);
            } else {
                $items[] = array_merge($resolver, [
                    'ok' => false,
                    'elapsed_ms' => $entry['elapsed_ms'] ?? round($timeout * 1000, 1),
                    'error' => 'Timeout',
                    'records' => [],
                    'answers' => [],
                ]);
            }
        }

        // Determine the majority consensus answers
        arsort($answerSignatures);
        $consensusAnswer = array_key_first($answerSignatures);
        $consensusCount = $consensusAnswer ? $answerSignatures[$consensusAnswer] : 0;
        $totalResolvers = count($resolvers);
        $propagationPercentage = $totalResolvers > 0 ? round(($consensusCount / $totalResolvers) * 100) : 0;

        return [
            'total' => $totalResolvers,
            'responding' => $successful,
            'consensus_answer' => $consensusAnswer,
            'propagation_percent' => $propagationPercentage,
            'items' => $items,
        ];
    }

    /**
     * Query a single custom DNS server via raw UDP socket.
     *
     * @return array<string, mixed>
     */
    public function queryDnsServerRaw(string $domain, string $server, string $type = 'A', float $timeout = 2.5): array
    {
        $types = [
            'A' => 1, 'NS' => 2, 'CNAME' => 5, 'SOA' => 6, 'MX' => 15, 'TXT' => 16, 'AAAA' => 28, 'ALL' => 255,
        ];
        $typeId = $types[strtoupper($type)] ?? 1;

        $id = rand(1, 65535);
        $header = pack('n6', $id, 0x0100, 1, 0, 0, 0);

        $qname = '';
        foreach (explode('.', trim($domain, '.')) as $part) {
            $qname .= chr(strlen($part)).$part;
        }
        $qname .= "\0";
        $question = $qname.pack('n2', $typeId, 1);
        $packet = $header.$question;

        $start = microtime(true);
        $sock = @stream_socket_client("udp://{$server}:53", $errno, $errstr, $timeout);
        if (! $sock) {
            return [
                'ok' => false,
                'error' => $errstr ?: "Unable to connect to nameserver {$server}:53",
                'elapsed_ms' => round((microtime(true) - $start) * 1000, 1),
                'records' => [],
                'count' => 0,
            ];
        }

        stream_set_timeout($sock, (int) floor($timeout), (int) (($timeout - floor($timeout)) * 1000000));
        @fwrite($sock, $packet);
        $response = @fread($sock, 1024);
        @fclose($sock);
        $elapsedMs = round((microtime(true) - $start) * 1000, 1);

        if (! $response || strlen($response) < 12) {
            return [
                'ok' => false,
                'error' => "No response from nameserver {$server} (timed out after {$timeout}s)",
                'elapsed_ms' => $elapsedMs,
                'records' => [],
                'count' => 0,
            ];
        }

        return $this->parseDnsResponsePacket($response, $domain, $types, $elapsedMs);
    }

    /**
     * Parse raw DNS UDP response wire format.
     *
     * @param  array<string, int>  $types
     * @return array<string, mixed>
     */
    private function parseDnsResponsePacket(string $response, string $domain, array $types, float $elapsedMs): array
    {
        $flags = unpack('n', substr($response, 2, 2))[1];
        $rcode = $flags & 0x000F;
        if ($rcode !== 0) {
            $rcodeNames = [
                1 => 'Format error',
                2 => 'Server failure (SERVFAIL)',
                3 => 'Domain not found (NXDOMAIN)',
                4 => 'Not implemented',
                5 => 'Query refused',
            ];

            return [
                'ok' => false,
                'error' => $rcodeNames[$rcode] ?? "DNS Error code {$rcode}",
                'elapsed_ms' => $elapsedMs,
                'records' => [],
                'count' => 0,
            ];
        }

        $ancount = unpack('n', substr($response, 6, 2))[1];
        $offset = 12;

        // Skip question section
        while ($offset < strlen($response) && ord($response[$offset]) !== 0) {
            if ((ord($response[$offset]) & 0xC0) === 0xC0) {
                $offset += 2;
                break;
            }
            $offset += 1 + ord($response[$offset]);
        }
        if ($offset < strlen($response) && ord($response[$offset]) === 0) {
            $offset++;
        }
        $offset += 4; // Skip QTYPE and QCLASS

        $readName = function (&$off) use ($response, &$readName) {
            $name = [];
            $jumped = false;
            $originalOff = $off;
            while ($off < strlen($response)) {
                $len = ord($response[$off]);
                if ($len === 0) {
                    $off++;
                    break;
                }
                if (($len & 0xC0) === 0xC0) {
                    $ptr = unpack('n', substr($response, $off, 2))[1] & 0x3FFF;
                    if (! $jumped) {
                        $originalOff = $off + 2;
                        $jumped = true;
                    }
                    $off = $ptr;

                    continue;
                }
                $off++;
                $name[] = substr($response, $off, $len);
                $off += $len;
            }
            if ($jumped) {
                $off = $originalOff;
            }

            return implode('.', $name);
        };

        $typeNames = array_flip($types);
        $records = [];

        for ($i = 0; $i < $ancount && $offset < strlen($response); $i++) {
            $recName = $readName($offset);
            if ($offset + 10 > strlen($response)) {
                break;
            }
            $rtype = unpack('n', substr($response, $offset, 2))[1];
            $rclass = unpack('n', substr($response, $offset + 2, 2))[1];
            $ttl = unpack('N', substr($response, $offset + 4, 4))[1];
            $rdlen = unpack('n', substr($response, $offset + 8, 2))[1];
            $offset += 10;
            $rdata = substr($response, $offset, $rdlen);
            $offset += $rdlen;

            $target = null;
            $tName = $typeNames[$rtype] ?? "TYPE{$rtype}";

            if ($rtype === 1 && $rdlen === 4) {
                $target = inet_ntop($rdata);
            } elseif ($rtype === 28 && $rdlen === 16) {
                $target = inet_ntop($rdata);
            } elseif ($rtype === 5 || $rtype === 2) {
                $tmpOff = $offset - $rdlen;
                $target = $readName($tmpOff);
            } elseif ($rtype === 15 && $rdlen > 2) {
                $pri = unpack('n', substr($rdata, 0, 2))[1];
                $tmpOff = $offset - $rdlen + 2;
                $target = $pri.' '.$readName($tmpOff);
            } elseif ($rtype === 16) {
                $target = substr($rdata, 1, ord($rdata[0]));
            } else {
                $target = bin2hex($rdata);
            }

            $records[] = [
                'host' => $recName ?: $domain,
                'type' => $tName,
                'ttl' => $ttl,
                'target' => $target,
            ];
        }

        return [
            'ok' => true,
            'count' => count($records),
            'records' => $records,
            'elapsed_ms' => $elapsedMs,
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
