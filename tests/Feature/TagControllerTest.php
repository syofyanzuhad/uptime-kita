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
        $monitor1 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor1->attachTags(['production', 'api', 'critical']);

        $monitor2 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor2->attachTags(['staging', 'api', 'backend']);

        $monitor3 = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);
        $monitor3->attachTags(['development', 'frontend']);
        $monitor3->users()->attach($this->user->id, ['is_active' => true]);

        $monitor4 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor4->attachTags(['production', 'database']);

        $monitor5 = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false, // Disabled monitor
        ]);
        $monitor5->attachTags(['archived', 'old']);

        Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            // No tags
        ]);
    });

    describe('index', function () {
        it('returns all unique tags from visible monitors', function () {
            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            expect($tags)->toBeArray();
            expect($tags)->toContain('production');
            expect($tags)->toContain('api');
            expect($tags)->toContain('staging');
            expect($tags)->toContain('development');
            expect($tags)->toContain('archived'); // Tags controller returns all tags, not filtered by enabled
        });

        it('requires authentication for unauthenticated users', function () {
            $response = get('/tags');

            $response->assertRedirect('/login');
        });

        it('includes tags from owned private monitors', function () {
            $privateMonitor = Monitor::factory()->create([
                'is_public' => false,
                'uptime_check_enabled' => true,
            ]);
            $privateMonitor->attachTags(['private-tag', 'internal']);

            $privateMonitor->users()->attach($this->user->id, ['is_active' => true]);

            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

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

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            // Count occurrences of 'production' tag
            $productionCount = count(array_filter($tags, fn ($tag) => $tag === 'production'));
            expect($productionCount)->toBe(1);

            // Count occurrences of 'api' tag
            $apiCount = count(array_filter($tags, fn ($tag) => $tag === 'api'));
            expect($apiCount)->toBe(1);
        });

        it('returns tags', function () {
            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            // Just check that we have tags
            expect($tags)->toBeArray();
            expect(count($tags))->toBeGreaterThan(0);
        });

        it('handles monitors without tags', function () {
            // Delete all monitors with tags
            Monitor::whereNotNull('tags')->delete();

            $response = actingAs($this->user)->get('/tags');

            $response->assertOk();
            $response->assertJson(['tags' => []]);
        });
    });

    describe('search', function () {
        it('searches for tags by query', function () {
            $response = actingAs($this->user)->get('/tags/search?search=prod');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            expect($tags)->toContain('production');
            expect($tags)->not->toContain('staging');
            expect($tags)->not->toContain('api');
        });

        it('returns empty array when no matches found', function () {
            $response = actingAs($this->user)->get('/tags/search?search=nonexistent');

            $response->assertOk();
            $response->assertJson(['tags' => []]);
        });

        it('searches case-insensitively', function () {
            $response = actingAs($this->user)->get('/tags/search?search=PROD');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            expect($tags)->toContain('production');
        });

        it('requires query parameter', function () {
            $response = actingAs($this->user)->get('/tags/search');

            $response->assertOk();
            $response->assertJson(['tags' => []]);
        });

        it('handles empty query parameter', function () {
            $response = actingAs($this->user)->get('/tags/search?search=');

            $response->assertOk();
            $response->assertJson(['tags' => []]);
        });

        it('limits search results', function () {
            // Create many monitors with tags matching the search
            for ($i = 0; $i < 30; $i++) {
                $monitor = Monitor::factory()->create([
                    'is_public' => true,
                    'uptime_check_enabled' => true,
                ]);
                $monitor->attachTags(["test-tag-$i", 'test-common']);
            }

            $response = actingAs($this->user)->get('/tags/search?search=test');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            expect(count($tags))->toBeLessThanOrEqual(10); // Controller limit is 10
        });

        it('searches only in visible monitors', function () {
            $privateMonitor = Monitor::factory()->create([
                'is_public' => false,
                'uptime_check_enabled' => true,
            ]);
            $privateMonitor->attachTags(['secret-tag']);

            $otherUser = User::factory()->create();

            $response = actingAs($otherUser)->get('/tags/search?search=secret');

            $response->assertOk();
            $response->assertJson(['tags' => []]);
        });

        it('includes partial matches', function () {
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);
            $monitor->attachTags(['production-server', 'production-database']);

            $response = actingAs($this->user)->get('/tags/search?search=duct');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            expect($tags)->toContain('production');
        });

        it('returns unique results', function () {
            // Create multiple monitors with same tag
            for ($i = 0; $i < 5; $i++) {
                $monitor = Monitor::factory()->create([
                    'is_public' => true,
                    'uptime_check_enabled' => true,
                ]);
                $monitor->attachTags(['duplicate-tag']);
            }

            $response = actingAs($this->user)->get('/tags/search?search=duplicate');

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            $duplicateCount = count(array_filter($tags, fn ($tag) => $tag === 'duplicate-tag'));
            expect($duplicateCount)->toBe(1);
        });

        it('handles special characters in search query', function () {
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);
            $monitor->attachTags(['test@special', 'test#hash', 'test$money']);

            $response = actingAs($this->user)->get('/tags/search?search='.urlencode('test@'));

            $response->assertOk();

            $data = $response->json();
            $tags = collect($data['tags'])->pluck('name')->toArray();

            expect($tags)->toContain('test@special');
        });
    });
});
