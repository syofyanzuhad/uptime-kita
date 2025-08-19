<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
});

describe('Monitor CRUD Operations', function () {

    describe('Index', function () {
        it('can list all monitors for authenticated user', function () {
            // Create monitors with uptime_check_enabled = true (required by global scope)
            $monitor1 = Monitor::factory()->create(['uptime_check_enabled' => true]);
            $monitor2 = Monitor::factory()->create(['uptime_check_enabled' => true]);

            // Attach monitors to user
            $monitor1->users()->attach($this->user->id);
            $monitor2->users()->attach($this->user->id);

            $response = actingAs($this->user)->get('/monitor');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('uptime/Index')
                ->has('monitors.data', 2)
            );
        });

        it('only shows monitors belonging to the user', function () {
            $myMonitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $otherMonitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $otherUser = User::factory()->create();

            $myMonitor->users()->attach($this->user->id);
            $otherMonitor->users()->attach($otherUser->id);

            $response = actingAs($this->user)->get('/monitor');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('uptime/Index')
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $myMonitor->id)
            );
        });

        it('admin sees all monitors', function () {
            $monitor1 = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $monitor2 = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $regularUser = User::factory()->create();

            $monitor1->users()->attach($regularUser->id);
            $monitor2->users()->attach($this->admin->id);

            $response = actingAs($this->admin)->get('/monitor');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('uptime/Index')
                ->has('monitors.data', 2)
            );
        });

        it('requires authentication', function () {
            $response = get('/monitor');

            $response->assertRedirect('/login');
        });
    });

    describe('Create', function () {
        it('can show create form for authenticated user', function () {
            $response = actingAs($this->user)->get('/monitor/create');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('uptime/Create')
            );
        });

        it('requires authentication to show create form', function () {
            $response = get('/monitor/create');

            $response->assertRedirect('/login');
        });
    });

    describe('Store', function () {
        it('can create a new monitor', function () {
            $monitorData = [
                'url' => 'https://example.com',
                'uptime_check_interval' => 5,
                'is_public' => true,
                'uptime_check_enabled' => true,
                'certificate_check_enabled' => false,
            ];

            $response = actingAs($this->user)->postJson('/monitor', $monitorData);

            $response->assertRedirect();

            assertDatabaseHas('monitors', [
                'url' => 'https://example.com',
                'uptime_check_interval_in_minutes' => 5,
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);

            // Verify user is attached to monitor
            $monitor = Monitor::where('url', 'https://example.com')->first();
            expect($monitor->users->pluck('id')->toArray())->toContain($this->user->id);
        });

        it('validates required fields when creating monitor', function () {
            $response = actingAs($this->user)->postJson('/monitor', []);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['url']);
        });

        it('validates URL format', function () {
            $monitorData = [
                'url' => 'not-a-valid-url',
                'uptime_check_interval' => 5,
            ];

            $response = actingAs($this->user)->postJson('/monitor', $monitorData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['url']);
        });

        it('requires authentication to create monitor', function () {
            $monitorData = [
                'url' => 'https://example.com',
                'uptime_check_interval' => 5,
            ];

            $response = postJson('/monitor', $monitorData);

            $response->assertUnauthorized();
        });
    });

    describe('Show', function () {
        it('can view a monitor belonging to user', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $monitor->users()->attach($this->user->id);

            $response = actingAs($this->user)->get("/monitor/{$monitor->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('uptime/Show')
                ->has('monitor')
            );
        });

        it('cannot view monitor not belonging to user', function () {
            $monitor = Monitor::factory()->private()->create();
            $otherUser = User::factory()->create();
            $monitor->users()->attach($otherUser->id);

            $response = actingAs($this->user)->get("/monitor/{$monitor->id}");

            $response->assertNotFound();
        });

        it('admin can view any monitor', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $regularUser = User::factory()->create();
            $monitor->users()->attach($regularUser->id);

            $response = actingAs($this->admin)->get("/monitor/{$monitor->id}");

            $response->assertSuccessful();
        });

        it('requires authentication to view monitor', function () {
            $monitor = Monitor::factory()->create();

            $response = get("/monitor/{$monitor->id}");

            $response->assertRedirect('/login');
        });
    });

    describe('Edit', function () {
        it('can show edit form for subscribed monitor', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $monitor->users()->attach($this->user->id);

            $response = actingAs($this->user)->get("/monitor/{$monitor->id}/edit");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('uptime/Edit')
                ->has('monitor')
            );
        });

        it('can show edit form if subscribed even if not owner', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $otherUser = User::factory()->create();
            $monitor->users()->attach($otherUser->id);
            // User is subscribed but not owner
            $monitor->users()->attach($this->user->id);

            $response = actingAs($this->user)->get("/monitor/{$monitor->id}/edit");

            // User is subscribed so can see edit form (but update will be blocked)
            $response->assertSuccessful();
        });

        it('admin can edit any monitor', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
            $regularUser = User::factory()->create();
            $monitor->users()->attach($regularUser->id);

            $response = actingAs($this->admin)->get("/monitor/{$monitor->id}/edit");

            $response->assertSuccessful();
        });
    });

    describe('Update', function () {
        it('can update owned monitor', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://old-url.com',
                'is_public' => false,
                'uptime_check_enabled' => true,
            ]);
            $monitor->users()->attach($this->user->id);

            $updateData = [
                'url' => 'https://new-url.com',
                'is_public' => true,
                'uptime_check_interval' => 10,
            ];

            $response = actingAs($this->user)->putJson("/monitor/{$monitor->id}", $updateData);

            $response->assertRedirect();

            assertDatabaseHas('monitors', [
                'id' => $monitor->id,
                'url' => 'https://new-url.com',
                'is_public' => true,
                'uptime_check_interval_in_minutes' => 10,
            ]);
        });

        it('cannot update monitor not owned by user', function () {
            $monitor = Monitor::factory()->private()->create();
            $otherUser = User::factory()->create();
            $monitor->users()->attach($otherUser->id);

            $updateData = [
                'url' => 'https://new-url.com',
            ];

            $response = actingAs($this->user)->putJson("/monitor/{$monitor->id}", $updateData);

            $response->assertNotFound();
        });

        it('admin can update any monitor', function () {
            $monitor = Monitor::factory()->create([
                'url' => 'https://old-url.com',
                'uptime_check_enabled' => true,
            ]);
            $regularUser = User::factory()->create();
            $monitor->users()->attach($regularUser->id);

            $updateData = [
                'url' => 'https://admin-updated.com',
                'uptime_check_interval' => 15,
            ];

            $response = actingAs($this->admin)->putJson("/monitor/{$monitor->id}", $updateData);

            $response->assertRedirect();

            assertDatabaseHas('monitors', [
                'id' => $monitor->id,
                'url' => 'https://admin-updated.com',
                'uptime_check_interval_in_minutes' => 15,
            ]);
        });

        it('validates URL format on update', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $monitor->users()->attach($this->user->id);

            $updateData = [
                'url' => 'invalid-url',
                'uptime_check_interval' => 5,
            ];

            $response = actingAs($this->user)->putJson("/monitor/{$monitor->id}", $updateData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['url']);
        });
    });

    describe('Delete', function () {
        it('can delete owned monitor', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $monitor->users()->attach($this->user->id);

            $response = actingAs($this->user)->deleteJson("/monitor/{$monitor->id}");

            $response->assertRedirect();
            assertDatabaseMissing('monitors', ['id' => $monitor->id]);
        });

        it('cannot delete monitor not owned by user', function () {
            $monitor = Monitor::factory()->private()->create();
            $otherUser = User::factory()->create();
            $monitor->users()->attach($otherUser->id);

            $response = actingAs($this->user)->deleteJson("/monitor/{$monitor->id}");

            $response->assertNotFound();
            assertDatabaseHas('monitors', ['id' => $monitor->id]);
        });

        it('admin can delete monitor they own', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $monitor->users()->attach($this->admin->id);

            $response = actingAs($this->admin)->deleteJson("/monitor/{$monitor->id}");

            $response->assertRedirect();
            assertDatabaseMissing('monitors', ['id' => $monitor->id]);
        });

        it('deleting monitor detaches all users', function () {
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true, 'is_public' => false]);
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $monitor->users()->attach([$user1->id, $user2->id]);

            assertDatabaseCount('user_monitor', 2);

            actingAs($user1)->deleteJson("/monitor/{$monitor->id}");

            // Monitor is deleted, so all users are detached
            assertDatabaseMissing('monitors', ['id' => $monitor->id]);
            assertDatabaseCount('user_monitor', 0);
        });
    });
});
