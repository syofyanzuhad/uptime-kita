<?php

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

test('checkSsl handles invalid connection gracefully', function () {
    $service = new PublicToolsService;
    $result = $service->checkSsl('non-existent-domain-12345xyz.invalid');

    expect($result['ok'])->toBeFalse()
        ->and($result['domain'])->toBe('non-existent-domain-12345xyz.invalid')
        ->and(isset($result['error']))->toBeTrue();
});
