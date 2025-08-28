<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageMonitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StatusPageOrderController', function () {
    it('updates monitor order successfully', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor1 = Monitor::factory()->create();
        $monitor2 = Monitor::factory()->create();
        $monitor3 = Monitor::factory()->create();

        // Associate monitors with user
        $monitor1->users()->attach($user->id);
        $monitor2->users()->attach($user->id);
        $monitor3->users()->attach($user->id);

        // Create status page monitors with initial order
        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor1->id,
            'order' => 0,
        ]);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor2->id,
            'order' => 1,
        ]);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor3->id,
            'order' => 2,
        ]);

        // Reorder monitors: monitor3, monitor1, monitor2
        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => [$monitor3->id, $monitor1->id, $monitor2->id],
        ]);

        $response->assertRedirect(route('status-pages.show', $statusPage->id));
        $response->assertSessionHas('success', 'Monitor order updated successfully.');

        // Verify the new order in database
        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor3->id,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor1->id,
            'order' => 1,
        ]);

        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor2->id,
            'order' => 2,
        ]);
    });

    it('validates monitor_ids are required', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", []);

        $response->assertSessionHasErrors(['monitor_ids']);
    });

    it('validates monitor_ids must be array', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => 'not-an-array',
        ]);

        $response->assertSessionHasErrors(['monitor_ids']);
    });

    it('validates each monitor ID exists in database', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => [999, 998], // Non-existent IDs
        ]);

        $response->assertSessionHasErrors(['monitor_ids.0', 'monitor_ids.1']);
    });

    it('skips monitors not associated with the status page', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor1 = Monitor::factory()->create();
        $monitor2 = Monitor::factory()->create(); // Not associated with status page

        // Associate monitors with user
        $monitor1->users()->attach($user->id);
        $monitor2->users()->attach($user->id);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor1->id,
            'order' => 0,
        ]);

        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => [$monitor2->id, $monitor1->id],
        ]);

        $response->assertRedirect(route('status-pages.show', $statusPage->id));

        // Verify monitor1 order was updated to position 1
        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor1->id,
            'order' => 1,
        ]);

        // Verify monitor2 is not in the status_page_monitor table
        $this->assertDatabaseMissing('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor2->id,
        ]);
    });

    it('skips updating if monitor already has the correct order', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor = Monitor::factory()->create();

        // Associate monitor with user
        $monitor->users()->attach($user->id);

        $statusPageMonitor = StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor->id,
            'order' => 0,
        ]);

        // Try to set the same order (position 0)
        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => [$monitor->id],
        ]);

        $response->assertRedirect(route('status-pages.show', $statusPage->id));

        // Verify the order remains unchanged
        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor->id,
            'order' => 0,
        ]);
    });

    it('handles empty monitor_ids array', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => [],
        ]);

        $response->assertSessionHasErrors(['monitor_ids']);
    });

    it('updates order for partial list of monitors', function () {
        $user = User::factory()->create();
        $statusPage = StatusPage::factory()->create(['user_id' => $user->id]);

        $monitor1 = Monitor::factory()->create();
        $monitor2 = Monitor::factory()->create();
        $monitor3 = Monitor::factory()->create();

        // Associate monitors with user
        $monitor1->users()->attach($user->id);
        $monitor2->users()->attach($user->id);
        $monitor3->users()->attach($user->id);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor1->id,
            'order' => 0,
        ]);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor2->id,
            'order' => 1,
        ]);

        StatusPageMonitor::factory()->create([
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor3->id,
            'order' => 2,
        ]);

        // Only reorder first two monitors
        $response = $this->actingAs($user)->post("/status-page-monitor/reorder/{$statusPage->id}", [
            'monitor_ids' => [$monitor2->id, $monitor1->id],
        ]);

        $response->assertRedirect(route('status-pages.show', $statusPage->id));

        // Verify updated orders
        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor2->id,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor1->id,
            'order' => 1,
        ]);

        // Monitor3 should remain unchanged
        $this->assertDatabaseHas('status_page_monitor', [
            'status_page_id' => $statusPage->id,
            'monitor_id' => $monitor3->id,
            'order' => 2,
        ]);
    });

    it('handles status page that does not exist', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/status-pages/999/order', [
            'monitor_ids' => [],
        ]);

        $response->assertNotFound();
    });
});
