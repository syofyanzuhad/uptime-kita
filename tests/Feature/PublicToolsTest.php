<?php

use App\Services\PublicToolsService;
use Inertia\Testing\AssertableInertia as Assert;

test('tools index page renders successfully', function () {
    $response = $this->get('/tools');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/Index')
        ->has('tools', 5)
    );
});

test('website checker page renders successfully', function () {
    $response = $this->get('/tools/website-checker');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/WebsiteChecker')
    );
});

test('ssl checker page renders successfully', function () {
    $response = $this->get('/tools/ssl-checker');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/SslChecker')
    );
});

test('dns lookup page renders successfully', function () {
    $response = $this->get('/tools/dns-lookup');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/DnsLookup')
    );
});

test('headers checker page renders successfully', function () {
    $response = $this->get('/tools/headers-checker');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('tools/HeadersChecker')
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
