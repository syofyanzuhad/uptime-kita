<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('TagController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        // Create monitors with various tags
        Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'tags' => ['production', 'api', 'critical'],
        ]);

        Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'tags' => ['staging', 'api', 'backend'],
        ]);

        Monitor::factory()->create([
            'is_public' => false,
            'is_enabled' => true,
            'tags' => ['development', 'frontend'],
        ])->users()->attach($this->user->id, ['is_owner' => true]);

        Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'tags' => ['production', 'database'],
        ]);

        Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => false, // Disabled monitor
            'tags' => ['archived', 'old'],
        ]);

        Monitor::factory()->create([
            'is_public' => true,
            'is_enabled' => true,
            'tags' => null, // No tags
        ]);
    });

    describe('index', function () {
        it('returns all unique tags from visible monitors', function () {
            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toBeArray();
            expect($tags)->toContain('production');
            expect($tags)->toContain('api');
            expect($tags)->toContain('staging');
            expect($tags)->toContain('development'); // From owned private monitor
            expect($tags)->not->toContain('archived'); // From disabled monitor
        });

        it('returns tags only from public monitors for unauthenticated users', function () {
            $response = get('/tags');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toContain('production');
            expect($tags)->toContain('api');
            expect($tags)->not->toContain('development'); // From private monitor
            expect($tags)->not->toContain('archived'); // From disabled monitor
        });

        it('includes tags from owned private monitors', function () {
            $privateMonitor = Monitor::factory()->create([
                'is_public' => false,
                'is_enabled' => true,
                'tags' => ['private-tag', 'internal'],
            ]);

            $privateMonitor->users()->attach($this->user->id, ['is_owner' => true]);

            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toContain('private-tag');
            expect($tags)->toContain('internal');
        });

        it('excludes tags from disabled monitors', function () {
            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->not->toContain('archived');
            expect($tags)->not->toContain('old');
        });

        it('returns unique tags without duplicates', function () {
            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $tags = $response->json();

            // Count occurrences of 'production' tag
            $productionCount = count(array_filter($tags, fn ($tag) => $tag === 'production'));
            expect($productionCount)->toBe(1);

            // Count occurrences of 'api' tag
            $apiCount = count(array_filter($tags, fn ($tag) => $tag === 'api'));
            expect($apiCount)->toBe(1);
        });

        it('returns sorted tags alphabetically', function () {
            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $tags = $response->json();
            $sortedTags = $tags;
            sort($sortedTags);

            expect($tags)->toBe($sortedTags);
        });

        it('handles monitors without tags', function () {
            // Delete all monitors with tags
            Monitor::whereNotNull('tags')->delete();

            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();
            $response->assertJson([]);
        });
    });

    describe('search', function () {
        it('searches for tags by query', function () {
            $response = actingAs($this->user)->get('/tags/search?q=prod');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toContain('production');
            expect($tags)->not->toContain('staging');
            expect($tags)->not->toContain('api');
        });

        it('returns empty array when no matches found', function () {
            $response = actingAs($this->user)->get('/tags/search?q=nonexistent');

            $response->assertOk();
            $response->assertJson([]);
        });

        it('searches case-insensitively', function () {
            $response = actingAs($this->user)->get('/tags/search?q=PROD');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toContain('production');
        });

        it('requires query parameter', function () {
            $response = actingAs($this->user)->get('/tags/search');

            $response->assertOk();
            $response->assertJson([]);
        });

        it('handles empty query parameter', function () {
            $response = actingAs($this->user)->get('/tags/search?q=');

            $response->assertOk();
            $response->assertJson([]);
        });

        it('limits search results', function () {
            // Create many monitors with tags matching the search
            for ($i = 0; $i < 30; $i++) {
                Monitor::factory()->create([
                    'is_public' => true,
                    'is_enabled' => true,
                    'tags' => ["test-tag-$i", 'test-common'],
                ]);
            }

            $response = actingAs($this->user)->get('/tags/search?q=test');

            $response->assertOk();

            $tags = $response->json();

            expect(count($tags))->toBeLessThanOrEqual(20); // Default limit
        });

        it('searches only in visible monitors', function () {
            $privateMonitor = Monitor::factory()->create([
                'is_public' => false,
                'is_enabled' => true,
                'tags' => ['secret-tag'],
            ]);

            $otherUser = User::factory()->create();

            $response = actingAs($otherUser)->get('/tags/search?q=secret');

            $response->assertOk();
            $response->assertJson([]);
        });

        it('includes partial matches', function () {
            Monitor::factory()->create([
                'is_public' => true,
                'is_enabled' => true,
                'tags' => ['production-server', 'production-database'],
            ]);

            $response = actingAs($this->user)->get('/tags/search?q=duct');

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toContain('production');
            expect($tags)->toContain('production-server');
            expect($tags)->toContain('production-database');
        });

        it('returns unique results', function () {
            // Create multiple monitors with same tag
            Monitor::factory()->count(5)->create([
                'is_public' => true,
                'is_enabled' => true,
                'tags' => ['duplicate-tag'],
            ]);

            $response = actingAs($this->user)->get('/tags/search?q=duplicate');

            $response->assertOk();

            $tags = $response->json();

            $duplicateCount = count(array_filter($tags, fn ($tag) => $tag === 'duplicate-tag'));
            expect($duplicateCount)->toBe(1);
        });

        it('handles special characters in search query', function () {
            Monitor::factory()->create([
                'is_public' => true,
                'is_enabled' => true,
                'tags' => ['test@special', 'test#hash', 'test$money'],
            ]);

            $response = actingAs($this->user)->get('/tags/search?q='.urlencode('test@'));

            $response->assertOk();

            $tags = $response->json();

            expect($tags)->toContain('test@special');
        });
    });
});
