<?php

use App\Models\Monitor;
use App\Models\StatusPage;
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

describe('StatusPage CRUD Operations', function () {

    describe('Index', function () {
        it('can list all status pages for authenticated user', function () {
            $statusPage1 = StatusPage::factory()->create(['user_id' => $this->user->id]);
            $statusPage2 = StatusPage::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->get('/status-pages');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('status-pages/Index')
                ->has('statusPages.data', 2)
            );
        });

        it('only shows status pages belonging to the user', function () {
            $myStatusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);
            $otherUser = User::factory()->create();
            $otherStatusPage = StatusPage::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->get('/status-pages');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('status-pages/Index')
                ->has('statusPages.data', 1)
                ->where('statusPages.data.0.id', $myStatusPage->id)
            );
        });

        it('admin sees only their own status pages', function () {
            $adminStatusPage = StatusPage::factory()->create(['user_id' => $this->admin->id]);
            $otherStatusPage = StatusPage::factory()->create();

            $response = actingAs($this->admin)->get('/status-pages');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('status-pages/Index')
                ->has('statusPages.data', 1)
            );
        });

        it('requires authentication', function () {
            $response = get('/status-pages');

            $response->assertRedirect('/login');
        });
    });

    describe('Create', function () {
        it('can show create form for authenticated user', function () {
            $response = actingAs($this->user)->get('/status-pages/create');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('status-pages/Create')
            );
        });

        it('requires authentication to show create form', function () {
            $response = get('/status-pages/create');

            $response->assertRedirect('/login');
        });
    });

    describe('Store', function () {
        it('can create a new status page', function () {
            $statusPageData = [
                'title' => 'My Status Page',
                'description' => 'A description of my status page',
                'path' => 'my-status-page',
                'icon' => 'default-icon.svg',
                'force_https' => true,
            ];

            $response = actingAs($this->user)->postJson('/status-pages', $statusPageData);

            $response->assertRedirect();

            assertDatabaseHas('status_pages', [
                'user_id' => $this->user->id,
                'title' => 'My Status Page',
                'description' => 'A description of my status page',
                'path' => 'my-status-page',
                'force_https' => true,
            ]);
        });

        it('validates required fields when creating status page', function () {
            $response = actingAs($this->user)->postJson('/status-pages', []);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['title']);
        });

        it('validates unique path', function () {
            StatusPage::factory()->create(['path' => 'existing-path']);

            $statusPageData = [
                'title' => 'New Status Page',
                'path' => 'existing-path',
            ];

            $response = actingAs($this->user)->postJson('/status-pages', $statusPageData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['path']);
        });

        it('requires authentication to create status page', function () {
            $statusPageData = [
                'title' => 'My Status Page',
                'path' => 'my-status-page',
            ];

            $response = postJson('/status-pages', $statusPageData);

            $response->assertUnauthorized();
        });
    });

    describe('Show', function () {
        it('can view a status page belonging to user', function () {
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->get("/status-pages/{$statusPage->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('status-pages/Show')
                ->has('statusPage')
                ->where('statusPage.id', $statusPage->id)
            );
        });

        it('cannot view status page not belonging to user', function () {
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->get("/status-pages/{$statusPage->id}");

            $response->assertForbidden();
        });

        it('admin cannot view other users status page', function () {
            $regularUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $regularUser->id]);

            $response = actingAs($this->admin)->get("/status-pages/{$statusPage->id}");

            $response->assertForbidden();
        });

        it('requires authentication to view status page', function () {
            $statusPage = StatusPage::factory()->create();

            $response = get("/status-pages/{$statusPage->id}");

            $response->assertRedirect('/login');
        });
    });

    describe('Edit', function () {
        it('can show edit form for owned status page', function () {
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->get("/status-pages/{$statusPage->id}/edit");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('status-pages/Edit')
                ->has('statusPage')
                ->where('statusPage.id', $statusPage->id)
            );
        });

        it('cannot edit status page not owned by user', function () {
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->get("/status-pages/{$statusPage->id}/edit");

            $response->assertForbidden();
        });

        it('admin cannot edit other users status page', function () {
            $regularUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $regularUser->id]);

            $response = actingAs($this->admin)->get("/status-pages/{$statusPage->id}/edit");

            $response->assertForbidden();
        });
    });

    describe('Update', function () {
        it('can update owned status page', function () {
            $statusPage = StatusPage::factory()->create([
                'user_id' => $this->user->id,
                'title' => 'Old Title',
                'description' => 'Old Description',
            ]);

            $updateData = [
                'title' => 'New Title',
                'description' => 'New Description',
                'path' => 'new-path',
                'icon' => 'default-icon.svg',
            ];

            $response = actingAs($this->user)->putJson("/status-pages/{$statusPage->id}", $updateData);

            $response->assertRedirect();

            assertDatabaseHas('status_pages', [
                'id' => $statusPage->id,
                'title' => 'New Title',
                'description' => 'New Description',
                'path' => 'new-path',
            ]);
        });

        it('cannot update status page not owned by user', function () {
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $otherUser->id]);

            $updateData = [
                'title' => 'Hacked Title',
            ];

            $response = actingAs($this->user)->putJson("/status-pages/{$statusPage->id}", $updateData);

            $response->assertForbidden();
        });

        it('admin cannot update other users status page', function () {
            $regularUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create([
                'user_id' => $regularUser->id,
                'title' => 'User Title',
            ]);

            $updateData = [
                'title' => 'Admin Updated Title',
                'description' => 'Admin updated this',
                'icon' => 'default-icon.svg',
            ];

            $response = actingAs($this->admin)->putJson("/status-pages/{$statusPage->id}", $updateData);

            $response->assertForbidden();

            assertDatabaseHas('status_pages', [
                'id' => $statusPage->id,
                'title' => 'User Title',
            ]);
        });

        it('validates unique path on update', function () {
            $existingPage = StatusPage::factory()->create(['path' => 'taken-path']);
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);

            $updateData = [
                'title' => 'Updated Title',
                'path' => 'taken-path',
            ];

            $response = actingAs($this->user)->putJson("/status-pages/{$statusPage->id}", $updateData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['path']);
        });
    });

    describe('Delete', function () {
        it('can delete owned status page', function () {
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->deleteJson("/status-pages/{$statusPage->id}");

            $response->assertRedirect();
            assertDatabaseMissing('status_pages', ['id' => $statusPage->id]);
        });

        it('cannot delete status page not owned by user', function () {
            $otherUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->deleteJson("/status-pages/{$statusPage->id}");

            $response->assertForbidden();
            assertDatabaseHas('status_pages', ['id' => $statusPage->id]);
        });

        it('admin cannot delete other users status page', function () {
            $regularUser = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['user_id' => $regularUser->id]);

            $response = actingAs($this->admin)->deleteJson("/status-pages/{$statusPage->id}");

            $response->assertForbidden();
            assertDatabaseHas('status_pages', ['id' => $statusPage->id]);
        });

        it('deleting status page removes monitor associations', function () {
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            $statusPage->monitors()->attach([$monitor1->id, $monitor2->id]);

            assertDatabaseCount('status_page_monitor', 2);

            actingAs($this->user)->deleteJson("/status-pages/{$statusPage->id}");

            assertDatabaseCount('status_page_monitor', 0);
            // But monitors should still exist
            assertDatabaseHas('monitors', ['id' => $monitor1->id]);
            assertDatabaseHas('monitors', ['id' => $monitor2->id]);
        });
    });

    describe('Monitor Association', function () {
        it('can associate monitors with status page', function () {
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);
            $monitor = Monitor::factory()->create();
            $monitor->users()->attach($this->user->id);

            $response = actingAs($this->user)->postJson("/status-pages/{$statusPage->id}/monitors", [
                'monitor_ids' => [$monitor->id],
            ]);

            // The endpoint returns a redirect, not a success
            $response->assertRedirect();

            assertDatabaseHas('status_page_monitor', [
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor->id,
            ]);
        });

        it('can disassociate monitors from status page', function () {
            $statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);
            // Create monitor with uptime_check_enabled = true (required by global scope)
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
            $monitor->users()->attach($this->user->id);
            $statusPage->monitors()->attach($monitor->id);

            $response = actingAs($this->user)->deleteJson("/status-pages/{$statusPage->id}/monitors/{$monitor->id}");

            // The endpoint returns a redirect, not a success
            $response->assertRedirect();

            assertDatabaseMissing('status_page_monitor', [
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor->id,
            ]);
        });
    });
});
