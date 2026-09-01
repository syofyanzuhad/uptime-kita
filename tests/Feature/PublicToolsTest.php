<?php

use App\Services\PublicToolsService;
use Inertia\Testing\AssertableInertia as Assert;

test('tools index page renders successfully', function () {
    $response = $this->get('/tools');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/Index')
        ->has('tools', 6)
    );
});

test('domain expiration checker page renders successfully', function () {
    $response = $this->get('/tools/domain-expiration');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/DomainExpirationChecker')
    );
});

test('domain expiration checker page renders with domain query parameter', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('checkDomainExpiration')
            ->once()
            ->with('google.com')
            ->andReturn(['ok' => true]);
    });

    $response = $this->get('/tools/domain-expiration?domain=google.com');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/DomainExpirationChecker')
        ->where('initialDomain', 'google.com')
        ->where('initialResult.ok', true)
    );
});

test('website checker page renders successfully', function () {
    $response = $this->get('/tools/website-checker?url=https://example.com');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/WebsiteChecker')
        ->where('initialUrl', 'https://example.com')
    );
});

test('ssl checker page renders with domain query parameter', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('checkSsl')
            ->once()
            ->with('google.com')
            ->andReturn(['ok' => true]);
    });

    $response = $this->get('/tools/ssl-checker?domain=google.com');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/SslChecker')
        ->where('initialDomain', 'google.com')
        ->where('initialResult.ok', true)
    );
});

test('dns lookup page renders with domain and type query parameter', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('lookupDns')
            ->once()
            ->with('google.com', 'A')
            ->andReturn(['ok' => true]);
    });

    $response = $this->get('/tools/dns-lookup?domain=google.com&type=A');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/DnsLookup')
        ->where('initialDomain', 'google.com')
        ->where('initialType', 'A')
        ->where('initialResult.ok', true)
    );
});

test('headers checker page renders with url query parameter', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('checkHeaders')
            ->once()
            ->with('https://example.com')
            ->andReturn(['ok' => true]);
    });

    $response = $this->get('/tools/headers-checker?url=https://example.com');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/HeadersChecker')
        ->where('initialUrl', 'https://example.com')
        ->where('initialResult.ok', true)
    );
});

test('badge generator page renders successfully', function () {
    $response = $this->get('/tools/badge-generator');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/BadgeGenerator')
    );
});

test('api ssl check returns valid schema', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('checkSsl')
            ->once()
            ->with('google.com')
            ->andReturn([
                'ok' => true,
                'domain' => 'google.com',
                'is_valid' => true,
                'days_remaining' => 60,
                'issuer' => 'Google Trust Services',
            ]);
    });

    $response = $this->postJson('/api/tools/ssl-check', [
        'domain' => 'google.com',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'ok' => true,
        'domain' => 'google.com',
        'is_valid' => true,
    ]);
});

test('api dns lookup returns valid schema', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('lookupDns')
            ->once()
            ->with('google.com', 'A')
            ->andReturn([
                'ok' => true,
                'domain' => 'google.com',
                'type' => 'A',
                'count' => 1,
                'records' => [
                    ['host' => 'google.com', 'type' => 'A', 'target' => '142.250.190.46', 'ttl' => 300],
                ],
                'elapsed_ms' => 12.5,
            ]);
    });

    $response = $this->postJson('/api/tools/dns-lookup', [
        'domain' => 'google.com',
        'type' => 'A',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'ok' => true,
        'domain' => 'google.com',
        'type' => 'A',
    ]);
});

test('api headers check returns valid schema', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('checkHeaders')
            ->once()
            ->with('https://google.com')
            ->andReturn([
                'ok' => true,
                'url' => 'https://google.com',
                'status_code' => 200,
                'score' => 'A',
                'security_headers' => [],
            ]);
    });

    $response = $this->postJson('/api/tools/headers-check', [
        'url' => 'https://google.com',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'ok' => true,
        'url' => 'https://google.com',
        'score' => 'A',
    ]);
});

test('api domain expiration check returns valid schema', function () {
    $this->mock(PublicToolsService::class, function ($mock) {
        $mock->shouldReceive('checkDomainExpiration')
            ->once()
            ->with('google.com')
            ->andReturn([
                'ok' => true,
                'domain' => 'google.com',
                'expires_at' => '2028-09-14T04:00:00.000000Z',
                'expires_at_formatted' => '2028-09-14 04:00:00 UTC',
                'days_remaining' => 745,
                'is_expired' => false,
            ]);
    });

    $response = $this->postJson('/api/tools/domain-expiration', [
        'domain' => 'google.com',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'ok' => true,
        'domain' => 'google.com',
        'is_expired' => false,
        'days_remaining' => 745,
    ]);
});
