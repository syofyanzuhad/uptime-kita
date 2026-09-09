<?php

use App\Services\DomainExpirationService;
use App\Services\PublicToolsService;
use Illuminate\Support\Facades\Http;

test('cleanDomain and normalizeUrl handle various formats correctly', function () {
    $service = new PublicToolsService;

    $reflection = new ReflectionClass($service);
    $cleanDomainMethod = $reflection->getMethod('cleanDomain');
    $cleanDomainMethod->setAccessible(true);

    $normalizeUrlMethod = $reflection->getMethod('normalizeUrl');
    $normalizeUrlMethod->setAccessible(true);

    expect($cleanDomainMethod->invoke($service, 'https://sub.domain.com/path?arg=1'))->toBe('sub.domain.com')
        ->and($cleanDomainMethod->invoke($service, 'http://example.org/'))->toBe('example.org')
        ->and($cleanDomainMethod->invoke($service, 'my-site.test'))->toBe('my-site.test');

    expect($normalizeUrlMethod->invoke($service, 'google.com'))->toBe('https://google.com')
        ->and($normalizeUrlMethod->invoke($service, 'http://insecure.test/'))->toBe('http://insecure.test')
        ->and($normalizeUrlMethod->invoke($service, 'https://secure.test/path/'))->toBe('https://secure.test/path');
});

test('checkHeaders returns complete security headers analysis', function () {
    Http::fake([
        'https://example.com' => Http::response('OK', 200, [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'",
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=()',
        ]),
    ]);

    $service = new PublicToolsService;
    $result = $service->checkHeaders('https://example.com');

    expect($result['ok'])->toBeTrue()
        ->and($result['status_code'])->toBe(200)
        ->and($result['score'])->toBe('A+')
        ->and($result['security_headers']['Strict-Transport-Security']['present'])->toBeTrue()
        ->and($result['security_headers']['Content-Security-Policy']['present'])->toBeTrue()
        ->and($result['security_headers']['X-Frame-Options']['present'])->toBeTrue();
});

test('checkHeaders handles lower grades and failures', function () {
    Http::fake([
        'https://insecure.com' => Http::response('OK', 200, [
            'X-Frame-Options' => 'SAMEORIGIN',
        ]),
        'https://failing.com' => function () {
            throw new Exception('Connection timed out');
        },
    ]);

    $service = new PublicToolsService;
    $result = $service->checkHeaders('https://insecure.com');

    expect($result['ok'])->toBeTrue()
        ->and($result['score'])->toBe('F')
        ->and($result['security_headers']['X-Frame-Options']['present'])->toBeTrue()
        ->and($result['security_headers']['Strict-Transport-Security']['present'])->toBeFalse();

    $failingResult = $service->checkHeaders('https://failing.com');
    expect($failingResult['ok'])->toBeFalse()
        ->and($failingResult['error'])->toContain('Connection failed: Connection timed out');
});

test('lookupDns handles successful and unknown records', function () {
    $service = new PublicToolsService;
    $result = $service->lookupDns('google.com', 'A');

    expect($result['ok'])->toBeTrue()
        ->and($result['domain'])->toBe('google.com')
        ->and($result['type'])->toBe('A')
        ->and(is_array($result['records']))->toBeTrue();
});

test('getGlobalDnsResolvers returns curated list of worldwide resolvers', function () {
    $service = new PublicToolsService;
    $resolvers = $service->getGlobalDnsResolvers();

    expect($resolvers)->toBeArray()
        ->and(count($resolvers))->toBeGreaterThanOrEqual(10);

    foreach ($resolvers as $resolver) {
        expect($resolver)->toHaveKeys(['id', 'name', 'ip', 'location', 'country', 'region', 'lat', 'lng'])
            ->and(filter_var($resolver['ip'], FILTER_VALIDATE_IP))->not->toBeFalse();
    }
});

test('parseDnsResponsePacket parses A, AAAA, CNAME, MX, TXT and error codes correctly', function () {
    $service = new PublicToolsService;
    $ref = new ReflectionClass($service);
    $parseMethod = $ref->getMethod('parseDnsResponsePacket');
    $parseMethod->setAccessible(true);
    $types = ['A' => 1, 'NS' => 2, 'CNAME' => 5, 'SOA' => 6, 'MX' => 15, 'TXT' => 16, 'AAAA' => 28, 'ALL' => 255];

    // 1. Successful A record response
    $id = 0x1234;
    $flags = 0x8180; // Standard query response, no error
    $header = pack('n6', $id, $flags, 1, 1, 0, 0);
    $question = "\x06google\x03com\x00".pack('n2', 1, 1);
    $answer = pack('n', 0xC00C).pack('n2', 1, 1).pack('N', 300).pack('n', 4).inet_pton('142.250.190.46');
    $packet = $header.$question.$answer;

    $parsed = $parseMethod->invoke($service, $packet, 'google.com', $types, 10.5);
    expect($parsed['ok'])->toBeTrue()
        ->and($parsed['count'])->toBe(1)
        ->and($parsed['records'][0]['target'])->toBe('142.250.190.46')
        ->and($parsed['records'][0]['type'])->toBe('A');

    // 2. NXDOMAIN error response (rcode 3)
    $errorFlags = 0x8183;
    $errorHeader = pack('n6', $id, $errorFlags, 1, 0, 0, 0);
    $errorPacket = $errorHeader.$question;

    $parsedError = $parseMethod->invoke($service, $errorPacket, 'nonexistent.domain', $types, 5.0);
    expect($parsedError['ok'])->toBeFalse()
        ->and($parsedError['error'])->toContain('NXDOMAIN');

    // 3. AAAA record response
    $headerAaaa = pack('n6', $id, $flags, 1, 1, 0, 0);
    $answerAaaa = pack('n', 0xC00C).pack('n2', 28, 1).pack('N', 300).pack('n', 16).inet_pton('2607:f8b0:4004:800::200e');
    $parsedAaaa = $parseMethod->invoke($service, $headerAaaa.$question.$answerAaaa, 'google.com', $types, 12.0);
    expect($parsedAaaa['ok'])->toBeTrue()
        ->and($parsedAaaa['records'][0]['type'])->toBe('AAAA')
        ->and($parsedAaaa['records'][0]['target'])->toBe('2607:f8b0:4004:800::200e');

    // 4. MX record response
    $headerMx = pack('n6', $id, $flags, 1, 1, 0, 0);
    $mxHost = "\x04mail\x06google\x03com\x00";
    $answerMx = pack('n', 0xC00C).pack('n2', 15, 1).pack('N', 300).pack('n', 2 + strlen($mxHost)).pack('n', 10).$mxHost;
    $parsedMx = $parseMethod->invoke($service, $headerMx.$question.$answerMx, 'google.com', $types, 15.0);
    expect($parsedMx['ok'])->toBeTrue()
        ->and($parsedMx['records'][0]['type'])->toBe('MX')
        ->and($parsedMx['records'][0]['target'])->toContain('10 mail.google.com');

    // 5. TXT record response
    $headerTxt = pack('n6', $id, $flags, 1, 1, 0, 0);
    $txtData = 'v=spf1 include:_spf.google.com ~all';
    $answerTxt = pack('n', 0xC00C).pack('n2', 16, 1).pack('N', 300).pack('n', 1 + strlen($txtData)).chr(strlen($txtData)).$txtData;
    $parsedTxt = $parseMethod->invoke($service, $headerTxt.$question.$answerTxt, 'google.com', $types, 8.0);
    expect($parsedTxt['ok'])->toBeTrue()
        ->and($parsedTxt['records'][0]['type'])->toBe('TXT')
        ->and($parsedTxt['records'][0]['target'])->toBe($txtData);

    // 6. CNAME record response
    $headerCname = pack('n6', $id, $flags, 1, 1, 0, 0);
    $cnameTarget = "\x03foo\x06google\x03com\x00";
    $answerCname = pack('n', 0xC00C).pack('n2', 5, 1).pack('N', 300).pack('n', strlen($cnameTarget)).$cnameTarget;
    $parsedCname = $parseMethod->invoke($service, $headerCname.$question.$answerCname, 'google.com', $types, 9.0);
    expect($parsedCname['ok'])->toBeTrue()
        ->and($parsedCname['records'][0]['type'])->toBe('CNAME')
        ->and($parsedCname['records'][0]['target'])->toBe('foo.google.com');
});

test('queryDnsServerRaw handles unreachable host gracefully', function () {
    $service = new PublicToolsService;
    // 192.0.2.1 is TEST-NET-1 (RFC 5737), guaranteed unroutable documentation IP
    $result = $service->queryDnsServerRaw('google.com', '192.0.2.1', 'A', 0.2);

    expect($result['ok'])->toBeFalse()
        ->and(isset($result['error']))->toBeTrue();
});

test('lookupDns supports custom public server query directly', function () {
    $service = new PublicToolsService;
    $result = $service->lookupDns('google.com', 'A', '8.8.8.8');

    expect($result)->toBeArray()
        ->and($result['server'])->toBe('8.8.8.8')
        ->and($result['domain'])->toBe('google.com')
        ->and($result['type'])->toBe('A');
});

test('checkSsl inspects live domain certificate successfully', function () {
    $service = new PublicToolsService;
    $result = $service->checkSsl('google.com');

    expect($result)->toBeArray()
        ->and($result['ok'])->toBeTrue()
        ->and($result['domain'])->toBe('google.com')
        ->and($result['is_valid'])->toBeTrue();
});

test('parseCertificateData processes certificate attributes correctly', function () {
    $service = new PublicToolsService;
    $validFrom = time() - 86400 * 10;
    $validTo = time() + 86400 * 50;

    $certData = [
        'validFrom_time_t' => $validFrom,
        'validTo_time_t' => $validTo,
        'issuer' => ['O' => 'Test Authority Ltd', 'CN' => 'Test Intermediate CA'],
        'subject' => ['CN' => 'my-app.example.com'],
        'extensions' => [
            'subjectAltName' => 'DNS:my-app.example.com, DNS:api.example.com, DNS:staging.example.com',
        ],
        'signatureTypeSN' => 'RSA-SHA256',
    ];

    $result = $service->parseCertificateData($certData, 'my-app.example.com');

    expect($result['ok'])->toBeTrue()
        ->and($result['is_valid'])->toBeTrue()
        ->and($result['days_remaining'])->toBeGreaterThan(48)
        ->and($result['issuer'])->toBe('Test Authority Ltd')
        ->and($result['subject'])->toBe('my-app.example.com')
        ->and($result['sans'])->toEqual(['my-app.example.com', 'api.example.com', 'staging.example.com'])
        ->and($result['signature_type'])->toBe('RSA-SHA256');
});

test('checkDomainExpiration handles empty domain, success, and service null results', function () {
    $mockExpirationService = Mockery::mock(DomainExpirationService::class);
    $mockExpirationService->shouldReceive('lookupExpirationDate')
        ->with('valid-domain.com')
        ->andReturn(now()->addDays(45));
    $mockExpirationService->shouldReceive('lookupExpirationDate')
        ->with('unresolvable.org')
        ->andReturn(null);

    $service = new PublicToolsService($mockExpirationService);

    // Empty / whitespace
    $emptyResult = $service->checkDomainExpiration('   ');
    expect($emptyResult['ok'])->toBeFalse()
        ->and($emptyResult['error'])->toBe('Invalid domain name.');

    // Valid
    $validResult = $service->checkDomainExpiration('valid-domain.com');
    expect($validResult['ok'])->toBeTrue()
        ->and($validResult['days_remaining'])->toBeGreaterThanOrEqual(44)
        ->and($validResult['is_expired'])->toBeFalse();

    // Unresolvable
    $unresolvableResult = $service->checkDomainExpiration('unresolvable.org');
    expect($unresolvableResult['ok'])->toBeFalse()
        ->and($unresolvableResult['error'])->toContain('Could not retrieve WHOIS/RDAP');
});

test('parallelDnsQuery executes multiple resolvers and computes consensus', function () {
    $service = new PublicToolsService;
    $resolvers = [
        [
            'id' => 'cf',
            'name' => 'Cloudflare',
            'ip' => '1.1.1.1',
            'location' => 'US',
            'country' => 'US',
            'country_code' => 'US',
            'flag' => '🇺🇸',
            'region' => 'North America',
            'lat' => 37.0,
            'lng' => -122.0,
        ],
        [
            'id' => 'google',
            'name' => 'Google',
            'ip' => '8.8.8.8',
            'location' => 'US',
            'country' => 'US',
            'country_code' => 'US',
            'flag' => '🇺🇸',
            'region' => 'North America',
            'lat' => 37.0,
            'lng' => -122.0,
        ],
    ];

    $result = $service->parallelDnsQuery('google.com', $resolvers, 'A', 1.0);

    expect($result)->toBeArray()
        ->and($result['total'])->toBe(2)
        ->and($result['items'])->toHaveCount(2)
        ->and(isset($result['propagation_percent']))->toBeTrue();
});

test('lookupDns includes global propagation when checkGlobal is true', function () {
    $service = new PublicToolsService;
    $result = $service->lookupDns('google.com', 'A', null, true);

    expect($result)->toBeArray()
        ->and($result['ok'])->toBeTrue()
        ->and(isset($result['global']))->toBeTrue()
        ->and($result['global']['total'])->toBeGreaterThanOrEqual(10);
});
