<?php

use Illuminate\Support\Facades\Http;

describe('DomainCheckController', function () {
    it('requires url parameter', function () {
        $this->getJson('/api/check-domain')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    });

    it('validates url max length', function () {
        $this->getJson('/api/check-domain?url='.str_repeat('a', 2049))
            ->assertStatus(422);
    });

    it('returns 422 for invalid url after normalization', function () {
        $this->getJson('/api/check-domain?url='.urlencode('ht!tp://[invalid'))
            ->assertStatus(422);
    });

    it('returns 422 for invalid domain without tld', function () {
        $this->getJson('/api/check-domain?url='.urlencode('invalid'))
            ->assertStatus(422);
    });

    it('normalizes url without scheme and returns success', function () {
        Http::fake(fn () => Http::response('', 200, ['Content-Type' => 'text/html', 'Server' => 'nginx']));

        $this->getJson('/api/check-domain?url='.urlencode('example.com'))
            ->assertOk()
            ->assertJsonPath('host', 'example.com')
            ->assertJsonPath('url', 'https://example.com')
            ->assertJsonPath('status_code', 200)
            ->assertJsonPath('ok', true);
    });

    it('accepts url with https scheme', function () {
        Http::fake(fn () => Http::response('', 200));

        $this->getJson('/api/check-domain?url='.urlencode('https://example.com'))
            ->assertOk()
            ->assertJsonPath('host', 'example.com');
    });

    it('accepts url with http scheme', function () {
        Http::fake(fn () => Http::response('', 200));

        $this->getJson('/api/check-domain?url='.urlencode('http://example.com'))
            ->assertOk()
            ->assertJsonPath('host', 'example.com');
    });

    it('returns ok true for 3xx redirect status', function () {
        Http::fake(fn () => Http::response('', 301));

        $this->getJson('/api/check-domain?url='.urlencode('https://example.com'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status_code', 301);
    });

    it('returns ok false for 5xx status', function () {
        Http::fake(fn () => Http::response('', 500));

        $this->getJson('/api/check-domain?url='.urlencode('https://example.com'))
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('status_code', 500);
    });

    it('returns response headers and timing', function () {
        Http::fake(fn () => Http::response('', 200, ['Content-Type' => 'text/html; charset=utf-8', 'Server' => 'cloudflare']));

        $response = $this->getJson('/api/check-domain?url='.urlencode('https://example.com'))
            ->assertOk();

        expect($response->json('headers.content-type'))->toBe('text/html; charset=utf-8');
        expect($response->json('headers.server'))->toBe('cloudflare');
        expect($response->json('response_time_ms'))->toBeInt();
    });

    it('trims whitespace from url', function () {
        Http::fake(fn () => Http::response('', 200));

        $this->getJson('/api/check-domain?url='.urlencode('  https://example.com  '))
            ->assertOk()
            ->assertJsonPath('host', 'example.com');
    });

    it('blocks private 127 address via prefix guard', function () {
        // Direct IP fails domain regex before SSRF guard — returns Invalid domain
        $this->getJson('/api/check-domain?url='.urlencode('http://127.0.0.1'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid domain.');

        // Domain starting with blocked prefix hits SSRF guard
        $this->getJson('/api/check-domain?url='.urlencode('http://127.example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Private/local addresses not allowed.');
    });

    it('blocks private 10 address', function () {
        $this->getJson('/api/check-domain?url='.urlencode('https://10.example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Private/local addresses not allowed.');
    });

    it('blocks private 192.168 address', function () {
        $this->getJson('/api/check-domain?url='.urlencode('https://192.168.example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Private/local addresses not allowed.');
    });

    it('blocks private 172.16-31 range', function () {
        $this->getJson('/api/check-domain?url='.urlencode('https://172.16.example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Private/local addresses not allowed.');
    });

    it('blocks link-local 169.254 address', function () {
        $this->getJson('/api/check-domain?url='.urlencode('https://169.254.example.com'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Private/local addresses not allowed.');
    });

    it('handles unreachable host with exception', function () {
        Http::fake(function () {
            throw new \Exception('cURL error 6: Could not resolve host: unreachable.invalid');
        });

        $response = $this->getJson('/api/check-domain?url='.urlencode('https://unreachable.invalid'))
            ->assertOk();

        expect($response->json('ok'))->toBeFalse();
        expect($response->json('status_code'))->toBeNull();
        expect($response->json('error'))->toBe('Could not reach host.');
        expect($response->json('response_time_ms'))->toBeInt();
    });

    it('returns exception message for non-curl errors', function () {
        Http::fake(function () {
            throw new \Exception('Connection timed out');
        });

        $response = $this->getJson('/api/check-domain?url='.urlencode('https://example.com'))
            ->assertOk();

        expect($response->json('error'))->toBe('Connection timed out');
    });

    it('blocks private address without scheme via normalization', function () {
        $this->getJson('/api/check-domain?url='.urlencode('127.0.0.1'))
            ->assertStatus(422);
    });
});
