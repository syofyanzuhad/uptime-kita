<?php

use Illuminate\Support\Facades\Http;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

test('it performs instant uptime check via GET /api/v1/check', function () {
    Http::fake([
        'https://example.com*' => Http::response('OK', 200, ['Server' => 'TestServer', 'Content-Type' => 'text/html']),
    ]);

    $response = getJson('/api/v1/check?url=example.com');

    $response->assertOk()
        ->assertJsonStructure([
            'ok',
            'status',
            'status_code',
            'response_time_ms',
            'url',
            'host',
            'headers' => ['content_type', 'server'],
            'checked_at',
        ])
        ->assertJson([
            'ok' => true,
            'status' => 'up',
            'status_code' => 200,
            'host' => 'example.com',
            'url' => 'https://example.com',
        ]);
});

test('it performs instant uptime check via POST /api/v1/check', function () {
    Http::fake([
        'https://laravel.com*' => Http::response('OK', 200, ['Content-Type' => 'text/html']),
    ]);

    $response = postJson('/api/v1/check', [
        'url' => 'laravel.com',
        'check_ssl' => false,
    ]);

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'status' => 'up',
            'status_code' => 200,
            'host' => 'laravel.com',
        ]);
});

test('it returns down status when target endpoint responds with 500 error', function () {
    Http::fake([
        'https://broken-service.com*' => Http::response('Internal Server Error', 500),
    ]);

    $response = getJson('/api/v1/check?url=https://broken-service.com');

    $response->assertOk()
        ->assertJson([
            'ok' => false,
            'status' => 'down',
            'status_code' => 500,
            'host' => 'broken-service.com',
        ]);
});

test('it returns 422 for invalid URLs', function () {
    $response = getJson('/api/v1/check?url=invalid_domain');

    $response->assertStatus(422)
        ->assertJsonStructure(['ok', 'status', 'message']);
});

test('it blocks SSRF attempts against private and local IPs', function (string $target) {
    $response = getJson('/api/v1/check?url='.$target);

    $response->assertStatus(422)
        ->assertJson([
            'ok' => false,
            'status' => 'error',
        ]);
})->with([
    '127.0.0.1',
    'localhost',
    '10.0.0.1',
    '192.168.1.1',
    '172.16.0.1',
    '169.254.169.254',
]);

test('it handles network exceptions gracefully', function () {
    Http::fake([
        'https://unreachable.org*' => function () {
            throw new Exception('cURL error 28: Operation timed out');
        },
    ]);

    $response = getJson('/api/v1/check?url=unreachable.org');

    $response->assertOk()
        ->assertJson([
            'ok' => false,
            'status' => 'down',
        ]);
});

test('it handles generic exceptions gracefully', function () {
    Http::fake([
        'https://generic-fail.org*' => function () {
            throw new Exception('Connection reset by peer');
        },
    ]);

    $response = getJson('/api/v1/check?url=generic-fail.org');

    $response->assertOk()
        ->assertJson([
            'ok' => false,
            'status' => 'down',
            'error' => 'Connection reset by peer',
        ]);
});
