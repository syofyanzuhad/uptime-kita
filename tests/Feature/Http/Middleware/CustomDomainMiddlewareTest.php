<?php

use App\Http\Middleware\CustomDomainMiddleware;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

function testMainAppDomainHandling()
{
    describe('main app domain handling', function () {
        it('skips middleware for main app domain', function () {
            $middleware = new CustomDomainMiddleware;
            $request = Request::create('https://localhost/test');

            $next = function ($req) {
                expect($req->attributes->get('custom_domain_status_page'))->toBeNull();

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('skips middleware for 127.0.0.1', function () {
            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://127.0.0.1/test');

            $next = function ($req) {
                expect($req->attributes->get('custom_domain_status_page'))->toBeNull();

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('skips middleware when host matches app domain from config', function () {
            config(['app.url' => 'https://uptime-kita.test']);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('https://uptime-kita.test/dashboard');

            $next = function ($req) {
                expect($req->attributes->get('custom_domain_status_page'))->toBeNull();

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });
    });
}

function testCustomDomainStatusPageHandling()
{
    describe('custom domain status page handling', function () {
        it('handles verified custom domain with status page', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'my-status',
                'custom_domain' => 'status.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://status.example.com/anything');

            $next = function ($req) use ($statusPage) {
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage->id);
                expect($retrievedStatusPage->path)->toBe($statusPage->path);
                expect($retrievedStatusPage->custom_domain)->toBe($statusPage->custom_domain);
                expect($req->get('path'))->toBe('my-status');
                expect($req->server->get('REQUEST_URI'))->toBe('/status/my-status');

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('ignores unverified custom domain', function () {
            $user = User::factory()->create();
            StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'my-status',
                'custom_domain' => 'unverified.example.com',
                'custom_domain_verified' => false, // Not verified
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://unverified.example.com/anything');

            $next = function ($req) {
                expect($req->attributes->get('custom_domain_status_page'))->toBeNull();
                expect($req->get('path'))->toBeNull();

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('ignores request for non-existent custom domain', function () {
            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://nonexistent.example.com/anything');

            $next = function ($req) {
                expect($req->attributes->get('custom_domain_status_page'))->toBeNull();
                expect($req->get('path'))->toBeNull();

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });
    });
}

function testHttpsRedirection()
{
    describe('HTTPS redirection', function () {
        it('redirects to HTTPS when force_https is enabled and request is not secure', function () {
            $user = User::factory()->create();
            StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'secure-status',
                'custom_domain' => 'secure.example.com',
                'custom_domain_verified' => true,
                'force_https' => true,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://secure.example.com/test-path');

            $next = function ($req) {
                // Should not reach here due to redirect
                return new Response('should not reach');
            };

            $response = $middleware->handle($request, $next);

            expect($response->getStatusCode())->toBe(302);
            $location = $response->headers->get('Location');
            expect($location)->toContain('https://');
            expect($location)->toContain('/test-path');
        });

        it('does not redirect when force_https is enabled but request is already secure', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'secure-status',
                'custom_domain' => 'secure.example.com',
                'custom_domain_verified' => true,
                'force_https' => true,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('https://secure.example.com/test-path');

            $next = function ($req) use ($statusPage) {
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage->id);

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('does not redirect when force_https is disabled', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'non-secure-status',
                'custom_domain' => 'nonsecure.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://nonsecure.example.com/test-path');

            $next = function ($req) use ($statusPage) {
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage->id);

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });
    });
}

function testRequestModification()
{
    describe('request modification', function () {
        it('correctly sets request attributes and parameters', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'test-path-123',
                'custom_domain' => 'custom.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://custom.example.com/some/original/path');

            $next = function ($req) use ($statusPage) {
                // Verify the status page is stored in request attributes
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage->id);
                expect($retrievedStatusPage->path)->toBe('test-path-123');

                // Verify the path parameter is merged
                expect($req->get('path'))->toBe('test-path-123');

                // Verify REQUEST_URI is overridden
                expect($req->server->get('REQUEST_URI'))->toBe('/status/test-path-123');

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('preserves original request data while adding custom domain data', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'preserve-test',
                'custom_domain' => 'preserve.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://preserve.example.com/original', 'GET', [
                'original_param' => 'original_value',
                'query_param' => 'query_value',
            ]);

            $next = function ($req) use ($statusPage) {
                // Original parameters should still be present
                expect($req->get('original_param'))->toBe('original_value');
                expect($req->get('query_param'))->toBe('query_value');

                // New path parameter should be added
                expect($req->get('path'))->toBe('preserve-test');

                // Status page should be in attributes
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage->id);

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });
    });
}

function testEdgeCases()
{
    describe('edge cases', function () {
        it('handles case-sensitive domain matching correctly', function () {
            $user = User::factory()->create();
            StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'case-test',
                'custom_domain' => 'CaSe.ExAmPlE.cOm',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            // Request with different case should not match due to case sensitivity
            $request = Request::create('http://case.example.com/test');

            $next = function ($req) {
                // Should not find status page due to case mismatch
                expect($req->attributes->get('custom_domain_status_page'))->toBeNull();

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('handles multiple status pages with different domains correctly', function () {
            $user = User::factory()->create();
            $statusPage1 = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'first-status',
                'custom_domain' => 'first.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $statusPage2 = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => 'second-status',
                'custom_domain' => 'second.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://first.example.com/test');

            $next = function ($req) use ($statusPage1) {
                // Should match only the first status page
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage1->id);
                expect($retrievedStatusPage->path)->toBe('first-status');
                expect($req->get('path'))->toBe('first-status');

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });

        it('handles status page with empty path gracefully', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $user->id,
                'path' => '',
                'custom_domain' => 'empty.example.com',
                'custom_domain_verified' => true,
                'force_https' => false,
            ]);

            $middleware = new CustomDomainMiddleware;
            $request = Request::create('http://empty.example.com/test');

            $next = function ($req) use ($statusPage) {
                $retrievedStatusPage = $req->attributes->get('custom_domain_status_page');
                expect($retrievedStatusPage)->not->toBeNull();
                expect($retrievedStatusPage->id)->toBe($statusPage->id);
                expect($req->get('path'))->toBe('');
                expect($req->server->get('REQUEST_URI'))->toBe('/status/');

                return new Response('success');
            };

            $response = $middleware->handle($request, $next);
            expect($response->getContent())->toBe('success');
        });
    });
}

describe('CustomDomainMiddleware', function () {
    testMainAppDomainHandling();
    testCustomDomainStatusPageHandling();
    testHttpsRedirection();
    testRequestModification();
    testEdgeCases();
});
