<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageMonitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StatusPageAvailableMonitorsController', function () {
    it('returns available monitors for status page owner', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor1 = Monitor::factory()->create(['display_name' => 'Available Monitor']);
        $monitor2 = Monitor::factory()->create(['display_name' => 'Used Monitor']);
        $monitor3 = Monitor::factory()->create(['display_name' => 'Another Available']);

        // Associate monitors with user
        $monitor1->users()->attach($user->id);
        $monitor2->users()->attach($user->id);
        $monitor3->users()->attach($user->id);

        // Add monitor2 to the status page
        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor2->id,
        ]);

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(2);
        // Note: MonitorResource returns 'name' field which contains the raw_url
        // We'll check for the IDs instead since display_name doesn't affect the URL
        $response->assertJsonPath('0.id', $monitor1->id);
        $response->assertJsonPath('1.id', $monitor3->id);
    });

    it('returns empty list when all monitors are already used', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor = Monitor::factory()->create();

        // Associate monitor with user
        $monitor->users()->attach($user->id);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor->id,
        ]);

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(0);
    });

    it('returns all monitors when none are used in status page', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitors = Monitor::factory()->count(3)->create();

        // Associate all monitors with user
        foreach ($monitors as $monitor) {
            $monitor->users()->attach($user->id);
        }

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(3);
    });

    it('only returns monitors owned by the authenticated user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $userMonitor = Monitor::factory()->create(['display_name' => 'User Monitor']);
        $otherUserMonitor = Monitor::factory()->create(['display_name' => 'Other User Monitor']);

        // Associate monitors with respective users
        $userMonitor->users()->attach($user->id);
        $otherUserMonitor->users()->attach($otherUser->id);

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(1);
        // Check ID instead since 'name' field contains raw_url
        $response->assertJsonPath('0.id', $userMonitor->id);
    });

    it('excludes monitors that are already assigned to the status page', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $assignedMonitor = Monitor::factory()->create(['display_name' => 'Assigned Monitor']);
        $availableMonitor = Monitor::factory()->create(['display_name' => 'Available Monitor']);

        // Associate monitors with user
        $assignedMonitor->users()->attach($user->id);
        $availableMonitor->users()->attach($user->id);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $assignedMonitor->id,
        ]);

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(1);
        // Check ID instead since 'name' field contains raw_url
        $response->assertJsonPath('0.id', $availableMonitor->id);
    });

    it('returns 403 for non-owner accessing status page', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertForbidden();
        $response->assertJson(['message' => 'Unauthorized.']);
    });

    it('requires authentication', function () {
        $statusPage = StatusPage::factory()->create();

        $response = $this->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertRedirect(route('login'));  // Laravel redirects to login for unauthenticated users
    });

    it('handles status page with monitors from different users', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $userMonitor1 = Monitor::factory()->create(['display_name' => 'User Monitor 1']);
        $userMonitor2 = Monitor::factory()->create(['display_name' => 'User Monitor 2']);
        $otherUserMonitor = Monitor::factory()->create(['display_name' => 'Other User Monitor']);

        // Associate monitors with respective users
        $userMonitor1->users()->attach($user->id);
        $userMonitor2->users()->attach($user->id);
        $otherUserMonitor->users()->attach($otherUser->id);

        // Assign userMonitor1 to the status page
        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $userMonitor1->id,
        ]);

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(1);
        // Check ID instead since 'name' field contains raw_url
        $response->assertJsonPath('0.id', $userMonitor2->id);
    });

    it('returns monitor resources with proper structure', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor = Monitor::factory()->create([
            'display_name' => 'Test Monitor',
            'url' => 'https://example.com',
        ]);

        // Associate monitor with user
        $monitor->users()->attach($user->id);

        $response = $this->actingAs($user)->get("/status-pages/{$statusPage->id}/available-monitors");

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',  // This will contain the raw_url
                'url',
            ],
        ]);
    });
});
