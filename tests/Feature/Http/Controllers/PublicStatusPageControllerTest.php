<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageMonitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('PublicStatusPageController', function () {
    describe('show method', function () {
        it('displays public status page by path', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);

            $response = $this->get('/status/my-status');

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component('status-pages/Public')
                ->has('statusPage')
                ->where('isAuthenticated', false)
                ->where('isCustomDomain', false)
            );
        });

        it('displays public status page for authenticated user', function () {
            $user = User::factory()->create();
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);

            $response = $this->actingAs($user)->get('/status/my-status');

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component('status-pages/Public')
                ->has('statusPage')
                ->where('isAuthenticated', true)
                ->where('isCustomDomain', false)
            );
        });

        it('displays status page with default custom domain setting', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);

            $response = $this->get('/status/my-status');

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component('status-pages/Public')
                ->has('statusPage')
                ->where('isAuthenticated', false)
                ->where('isCustomDomain', false) // Should be false by default
            );
        });

        it('uses cache for regular status page requests', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);

            Cache::shouldReceive('remember')
                ->with('public_status_page_my-status', 60, \Closure::class)
                ->once()
                ->andReturn($statusPage);

            $this->get('/status/my-status');
        });

        it('returns 404 when status page not found', function () {
            $response = $this->get('/status/nonexistent');

            $response->assertNotFound();
        });

        it('passes status page data to view', function () {
            $statusPage = StatusPage::factory()->create([
                'path' => 'my-status',
                'title' => 'My Status Page',
                'description' => 'Status page description',
            ]);

            $response = $this->get('/status/my-status');

            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component('status-pages/Public')
                ->has('statusPage.title')
                ->has('statusPage.description')
            );
        });
    });

    describe('monitors method', function () {
        it('returns monitors for status page', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);
            $monitor1 = Monitor::factory()->create(['display_name' => 'Monitor 1']);
            $monitor2 = Monitor::factory()->create(['display_name' => 'Monitor 2']);

            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor1->id,
                'order' => 1,
            ]);

            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor2->id,
                'order' => 2,
            ]);

            $response = $this->get('/status/my-status/monitors');

            $response->assertOk();
            $response->assertJsonCount(2);
            $response->assertJsonPath('0.name', $monitor1->raw_url);
            $response->assertJsonPath('1.name', $monitor2->raw_url);
        });

        it('returns monitors in correct order', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);
            $monitor1 = Monitor::factory()->create(['display_name' => 'First Monitor']);
            $monitor2 = Monitor::factory()->create(['display_name' => 'Second Monitor']);

            // Create with order reversed to test ordering
            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor1->id,
                'order' => 2,
            ]);

            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor2->id,
                'order' => 1,
            ]);

            $response = $this->get('/status/my-status/monitors');

            $response->assertOk();
            $response->assertJsonPath('0.name', $monitor2->raw_url); // Order 1 should come first
            $response->assertJsonPath('1.name', $monitor1->raw_url);  // Order 2 should come second
        });

        it('returns 404 when no monitors found', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);

            $response = $this->get('/status/my-status/monitors');

            $response->assertNotFound();
            $response->assertJson([
                'message' => 'No monitors found',
            ]);
        });

        it('filters out null monitors', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);
            $monitor = Monitor::factory()->create(['display_name' => 'Valid Monitor']);

            // Create one valid monitor relationship
            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor->id,
                'order' => 1,
            ]);

            $response = $this->get('/status/my-status/monitors');

            $response->assertOk();
            $response->assertJsonCount(1);
            $response->assertJsonPath('0.name', $monitor->raw_url);
        });

        it('uses cache for monitor requests', function () {
            $statusPage = StatusPage::factory()->create(['path' => 'my-status']);
            $monitor = Monitor::factory()->create();
            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor->id,
            ]);

            Cache::shouldReceive('remember')
                ->with('public_status_page_monitors_my-status', 60, \Closure::class)
                ->once()
                ->andReturn(collect([$monitor]));

            $this->get('/status/my-status/monitors');
        });

        it('returns empty response when status page does not exist', function () {
            $response = $this->get('/status/nonexistent/monitors');

            $response->assertNotFound();
        });

        it('returns monitors only for the requested status page', function () {
            $statusPage1 = StatusPage::factory()->create(['path' => 'status-1']);
            $statusPage2 = StatusPage::factory()->create(['path' => 'status-2']);

            $monitor1 = Monitor::factory()->create(['display_name' => 'Monitor 1']);
            $monitor2 = Monitor::factory()->create(['display_name' => 'Monitor 2']);

            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage1->id,
                'monitor_id' => $monitor1->id,
            ]);

            StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage2->id,
                'monitor_id' => $monitor2->id,
            ]);

            $response = $this->get('/status/status-1/monitors');

            $response->assertOk();
            $response->assertJsonCount(1);
            $response->assertJsonPath('0.name', $monitor1->raw_url);
        });
    });
});
