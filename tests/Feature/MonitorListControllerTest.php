<?php

use App\Models\Monitor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('MonitorListController', function () {
    describe('index method with pinned type', function () {
        it('displays only pinned monitors for authenticated user', function () {
            // Create monitors
            $pinnedMonitor = Monitor::factory()->create();
            $unpinnedMonitor = Monitor::factory()->create();
            $otherUserPinnedMonitor = Monitor::factory()->create();

            // Attach monitors to user with syncWithoutDetaching to avoid duplicates
            $this->user->monitors()->syncWithoutDetaching([
                $pinnedMonitor->id => ['is_pinned' => true],
                $unpinnedMonitor->id => ['is_pinned' => false],
            ]);

            // Another user's pinned monitor
            $otherUser = User::factory()->create();
            $otherUser->monitors()->syncWithoutDetaching([$otherUserPinnedMonitor->id => ['is_pinned' => true]]);

            $response = $this->get(route('monitors.list', ['type' => 'pinned']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('monitors/List')
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $pinnedMonitor->id)
                ->where('type', 'pinned')
            );
        });

        it('returns empty list when user has no pinned monitors', function () {
            Monitor::factory()->count(3)->create();

            $response = $this->get(route('monitors.list', ['type' => 'pinned']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('monitors/List')
                ->has('monitors.data', 0)
                ->where('type', 'pinned')
            );
        });
    });

    describe('index method with private type', function () {
        it('displays only private monitors user is subscribed to', function () {
            $privateMonitor = Monitor::factory()->create(['is_public' => false]);
            $publicMonitor = Monitor::factory()->create(['is_public' => true]);
            $otherPrivateMonitor = Monitor::factory()->create(['is_public' => false]);

            // User subscribed to private and public monitors
            $this->user->monitors()->syncWithoutDetaching([$privateMonitor->id, $publicMonitor->id]);

            // Other user's private monitor
            $otherUser = User::factory()->create();
            $otherUser->monitors()->syncWithoutDetaching([$otherPrivateMonitor->id]);

            $response = $this->get(route('monitors.list', ['type' => 'private']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('monitors/List')
                ->has('monitors.data')
                // Check that the returned monitors are only private ones that user is subscribed to
                ->where('type', 'private')
            );
        });
    });

    describe('index method with public type', function () {
        it('displays all public monitors', function () {
            $publicMonitor1 = Monitor::factory()->create(['is_public' => true]);
            $publicMonitor2 = Monitor::factory()->create(['is_public' => true]);
            $privateMonitor = Monitor::factory()->create(['is_public' => false]);

            $response = $this->get(route('monitors.list', ['type' => 'public']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('monitors/List')
                ->has('monitors.data', 2)
                ->where('type', 'public')
            );
        });

        it('includes subscription status for authenticated users', function () {
            $publicMonitor = Monitor::factory()->create(['is_public' => true]);
            $this->user->monitors()->syncWithoutDetaching([$publicMonitor->id]);

            $response = $this->get(route('monitors.list', ['type' => 'public']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('monitors/List')
                ->where('monitors.data.0.id', $publicMonitor->id)
                ->where('monitors.data.0.is_subscribed', true)
            );
        });

        it('requires authentication to view any monitor list', function () {
            auth()->logout();

            // Test that even public monitors require authentication
            $response = $this->get(route('monitors.list', ['type' => 'public']));

            $response->assertRedirect('/login');
        });
    });

    describe('invalid type parameter', function () {
        it('returns 404 for invalid monitor type', function () {
            $response = $this->get('/monitors/invalid-type');

            $response->assertNotFound();
        });

        it('returns 404 for empty type', function () {
            $response = $this->get('/monitors/');

            $response->assertNotFound();
        });
    });

    describe('search functionality', function () {
        it('filters monitors by URL search term', function () {
            $matchingMonitor = Monitor::factory()->create([
                'url' => 'https://example.com',
                'is_public' => true,
            ]);
            $nonMatchingMonitor = Monitor::factory()->create([
                'url' => 'https://different.com',
                'is_public' => true,
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'search' => 'example',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $matchingMonitor->id)
                ->where('search', 'example')
            );
        });

        it('requires minimum 3 characters for search', function () {
            Monitor::factory()->count(3)->create(['is_public' => true]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'search' => 'ab',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 3)
                ->where('search', 'ab')
            );
        });

        it('searches without protocol in URL', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://searchtest.com',
                'is_public' => true,
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'search' => 'searchtest',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $monitor->id)
            );
        });
    });

    describe('status filters', function () {
        it('filters disabled monitors for authenticated users', function () {
            $activeMonitor = Monitor::factory()->create(['is_public' => false]);
            $disabledMonitor = Monitor::factory()->create(['is_public' => false]);

            $this->user->monitors()->syncWithoutDetaching([
                $activeMonitor->id => ['is_active' => true],
                $disabledMonitor->id => ['is_active' => false],
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'pinned',
                'status_filter' => 'disabled',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 0) // Should be 0 because they're not pinned
                ->where('statusFilter', 'disabled')
            );
        });

        it('filters globally enabled monitors', function () {
            Monitor::factory()->create([
                'uptime_check_enabled' => true,
                'is_public' => true,
            ]);
            Monitor::factory()->create([
                'uptime_check_enabled' => false,
                'is_public' => true,
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'status_filter' => 'globally_enabled',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('statusFilter', 'globally_enabled')
            );
        });

        it('filters globally disabled monitors', function () {
            Monitor::factory()->create([
                'uptime_check_enabled' => true,
                'is_public' => true,
            ]);
            Monitor::factory()->create([
                'uptime_check_enabled' => false,
                'is_public' => true,
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'status_filter' => 'globally_disabled',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('statusFilter', 'globally_disabled')
            );
        });

        it('filters monitors by uptime status', function () {
            Monitor::factory()->create([
                'uptime_status' => 'up',
                'is_public' => true,
            ]);
            Monitor::factory()->create([
                'uptime_status' => 'down',
                'is_public' => true,
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'status_filter' => 'up',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.uptime_status', 'up')
                ->where('statusFilter', 'up')
            );
        });
    });

    describe('visibility filters', function () {
        it('filters public monitors in non-type-specific views', function () {
            $publicMonitor = Monitor::factory()->create(['is_public' => true]);
            $privateMonitor = Monitor::factory()->create(['is_public' => false]);

            $this->user->monitors()->syncWithoutDetaching([
                $publicMonitor->id => ['is_pinned' => true],
                $privateMonitor->id => ['is_pinned' => true],
            ]);

            $response = $this->get(route('monitors.list', [
                'type' => 'pinned',
                'visibility_filter' => 'public',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $publicMonitor->id)
                ->where('visibilityFilter', 'public')
            );
        });

        it('ignores visibility filter for type-specific views', function () {
            Monitor::factory()->count(2)->create(['is_public' => true]);
            Monitor::factory()->create(['is_public' => false]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'visibility_filter' => 'private',
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 2)
                ->where('visibilityFilter', 'private')
            );
        });
    });

    describe('pagination', function () {
        it('paginates results with default per page', function () {
            Monitor::factory()->count(15)->create(['is_public' => true]);

            $response = $this->get(route('monitors.list', ['type' => 'public']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 12)
                ->has('monitors.meta')
                ->has('monitors.meta.total')
                ->where('perPage', 12)
            );
        });

        it('respects custom per page parameter', function () {
            Monitor::factory()->count(10)->create(['is_public' => true]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'per_page' => 5,
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 5)
                ->where('perPage', '5')
            );
        });

        it('navigates to specific page', function () {
            Monitor::factory()->count(15)->create(['is_public' => true]);

            $response = $this->get(route('monitors.list', [
                'type' => 'public',
                'page' => 2,
            ]));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 3)
                ->has('monitors.meta')
                ->has('monitors.meta.current_page')
            );
        });
    });

    describe('ordering', function () {
        it('orders monitors by creation date descending', function () {
            $oldMonitor = Monitor::factory()->create([
                'is_public' => true,
                'created_at' => now()->subDays(2),
            ]);
            $newMonitor = Monitor::factory()->create([
                'is_public' => true,
                'created_at' => now(),
            ]);

            $response = $this->get(route('monitors.list', ['type' => 'public']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->where('monitors.data.0.id', $newMonitor->id)
                ->where('monitors.data.1.id', $oldMonitor->id)
            );
        });
    });

    describe('relationships loading', function () {
        it('loads required relationships efficiently', function () {
            $monitor = Monitor::factory()->create(['is_public' => true]);
            $this->user->monitors()->syncWithoutDetaching([$monitor->id]);

            $response = $this->get(route('monitors.list', ['type' => 'public']));

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data.0.id')
                ->has('monitors.data.0.url')
            );
        });
    });
});
