<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Spatie\Sitemap\SitemapGenerator;

uses(RefreshDatabase::class);

describe('GenerateSitemap', function () {
    describe('handle', function () {
        afterEach(function () {
            // Clean up generated sitemap file after each test
            if (file_exists(public_path('sitemap.xml'))) {
                unlink(public_path('sitemap.xml'));
            }
        });

        it('generates sitemap successfully', function () {
            // Mock the HTTP requests that SitemapGenerator might make
            Http::fake([
                config('app.url').'*' => Http::response('<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>', 200),
            ]);

            // Ensure sitemap file doesn't exist before test
            expect(file_exists(public_path('sitemap.xml')))->toBeFalse();

            $this->artisan('sitemap:generate')
                ->assertSuccessful();

            // Verify sitemap.xml was created
            expect(file_exists(public_path('sitemap.xml')))->toBeTrue();

            // Verify the file contains XML content
            $content = file_get_contents(public_path('sitemap.xml'));
            expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
            expect($content)->toContain('<urlset');
        });

        it('overwrites existing sitemap file', function () {
            // Create an existing sitemap file with old content
            $oldContent = '<?xml version="1.0" encoding="UTF-8"?><urlset><url><loc>old-url</loc></url></urlset>';
            file_put_contents(public_path('sitemap.xml'), $oldContent);

            expect(file_exists(public_path('sitemap.xml')))->toBeTrue();
            expect(file_get_contents(public_path('sitemap.xml')))->toContain('old-url');

            // Mock HTTP requests
            Http::fake([
                config('app.url').'*' => Http::response('<html><head><title>Test</title></head><body><h1>Test Page</h1></body></html>', 200),
            ]);

            $this->artisan('sitemap:generate')
                ->assertSuccessful();

            // Verify file still exists and content has been updated
            expect(file_exists(public_path('sitemap.xml')))->toBeTrue();
            $newContent = file_get_contents(public_path('sitemap.xml'));
            expect($newContent)->not->toContain('old-url');
            expect($newContent)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
        });

        it('uses app.url configuration for sitemap generation', function () {
            $this->artisan('sitemap:generate')
                ->assertSuccessful();

            expect(file_exists(public_path('sitemap.xml')))->toBeTrue();

            // Verify the sitemap was generated with valid XML structure
            $content = file_get_contents(public_path('sitemap.xml'));
            expect($content)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
            expect($content)->toContain('<urlset');

            // The sitemap should be well-formed XML even if empty
            expect($content)->toContain('</urlset>');
        });

        it('handles network errors gracefully', function () {
            // Mock network failure
            Http::fake([
                config('app.url').'*' => Http::response('', 500),
            ]);

            // The command should still complete (SitemapGenerator handles errors internally)
            $this->artisan('sitemap:generate')
                ->assertSuccessful();
        });

        it('creates sitemap in public directory', function () {
            // Mock successful HTTP responses
            Http::fake([
                config('app.url').'*' => Http::response('<html><head><title>Test</title></head><body></body></html>', 200),
            ]);

            $expectedPath = public_path('sitemap.xml');
            expect(file_exists($expectedPath))->toBeFalse();

            $this->artisan('sitemap:generate')
                ->assertSuccessful();

            // Verify the exact path where sitemap was created
            expect(file_exists($expectedPath))->toBeTrue();
            expect(is_readable($expectedPath))->toBeTrue();
        });
    });
});
