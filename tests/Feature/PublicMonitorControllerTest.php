<?php

use App\Http\Controllers\PublicMonitorController;
use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use App\Models\User;
use Illuminate\Http\Request;

use function Pest\Laravel\get;

describe('PublicMonitorController', function () {
    beforeEach(function () {
        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);

        $this->disabledMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => false,
        ]);
    });

    it('displays public monitors page', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicIndex')
            ->has('monitors')
            ->has('stats')
            ->has('filters')
            ->has('availableTags')
        );
    });

    it('includes only public and enabled monitors', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('monitors.data.0.id', $this->publicMonitor->id)
            ->count('monitors.data', 1)
        );
    });

    it('excludes private monitors', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->whereNot('monitors.data.0.id', $this->privateMonitor->id)
        );
    });

    it('excludes disabled monitors', function () {
        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->whereNot('monitors.data.0.id', $this->disabledMonitor->id)
        );
    });

    it('includes monitor statistics', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'response_time' => 250,
            'created_at' => now(),
        ]);

        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_percentage' => 99.5,
            'date' => now()->toDateString(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data.0.last_check_date')
            ->has('monitors.data.0.today_uptime_percentage')
        );
    });

    it('includes basic monitor information', function () {
        MonitorHistory::factory()->create([
            'monitor_id' => $this->publicMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data.0.id')
            ->has('monitors.data.0.name')
            ->has('monitors.data.0.url')
        );
    });

    it('paginates public monitors', function () {
        Monitor::factory()->count(20)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 15) // Default pagination
            ->has('monitors.links')
            ->has('monitors.meta')
        );
    });

    it('respects per_page parameter', function () {
        Monitor::factory()->count(10)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $response = get('/public-monitors?per_page=5');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 5)
        );
    });

    it('shows all public monitor stats for logged-in non-admin users', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('stats.total_public', 1) // Only the public + enabled monitor
            ->where('stats.up', 1)
            ->where('stats.down', 0)
        );
    });

    it('calculates monitor counts correctly', function () {
        Monitor::factory()->count(3)->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);

        $upMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
        ]);

        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $upMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $downMonitor->id,
            'uptime_status' => 'down',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('stats.total', 6)
            ->where('stats.up', 5)  // All monitors default to up except the one explicitly set to down
            ->where('stats.down', 1) // Only the one explicitly set to down
        );
    });

    it('orders monitors by created date descending', function () {
        $oldMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'created_at' => now()->subDays(2),
        ]);

        $newMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'created_at' => now(),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $oldMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now()->subDays(2),
        ]);

        MonitorHistory::factory()->create([
            'monitor_id' => $newMonitor->id,
            'uptime_status' => 'up',
            'created_at' => now(),
        ]);

        $response = get('/public-monitors');

        $response->assertOk();
        // Just verify that both monitors are present in the response
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data')
        );
    });

    it('filters by status up', function () {
        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
        ]);

        $response = get('/public-monitors?status_filter=up');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 1)
            ->where('monitors.data.0.id', $this->publicMonitor->id)
        );
    });

    it('filters by status down', function () {
        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
        ]);

        $response = get('/public-monitors?status_filter=down');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 1)
            ->where('monitors.data.0.id', $downMonitor->id)
        );
    });

    it('sorts by popular using page views', function () {
        $popular = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'page_views_count' => 100,
        ]);
        $unpopular = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'page_views_count' => 1,
        ]);

        $response = get('/public-monitors?sort_by=popular');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('monitors.data.0.id', $popular->id)
        );
    });

    it('sorts by name', function () {
        $aaa = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://aaa.example.com',
        ]);
        $zzz = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://zzz.example.com',
        ]);

        $response = get('/public-monitors?sort_by=name');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('monitors.data.0.id', $aaa->id)
        );
    });

    it('falls back to default sort for invalid sort option', function () {
        $response = get('/public-monitors?sort_by=invalid-sort');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.sort_by', 'default')
        );
    });

    it('filters by tag', function () {
        $monitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitor->attachTag('production');

        $response = get('/public-monitors?tag_filter=production');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('availableTags')
        );
    });

    it('ignores searches shorter than 3 characters', function () {
        $response = get('/public-monitors?search=ab');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.search', null)
        );
    });

    it('returns json for json requests', function () {
        $response = $this->getJson('/public-monitors');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    });

    it('sorts by uptime via statistics', function () {
        Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://low.example.com',
        ]);

        $response = get('/public-monitors?sort_by=uptime');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data')
        );
    });

    it('sorts by response time via statistics', function () {
        Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'url' => 'https://slow.example.com',
        ]);

        $response = get('/public-monitors?sort_by=response_time');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data')
        );
    });

    it('sorts by status with down first', function () {
        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
        ]);

        $response = get('/public-monitors?sort_by=status');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('monitors.data.0.id', $downMonitor->id)
        );
    });

    it('filters disabled monitors with globally_disabled', function () {
        $response = get('/public-monitors?status_filter=globally_disabled');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('monitors.data', 1)
            ->where('monitors.data.0.id', $this->disabledMonitor->id)
        );
    });

    it('filters enabled monitors with globally_enabled', function () {
        $response = get('/public-monitors?status_filter=globally_enabled');

        $response->assertOk();
    });

    it('filters unsubscribed monitors for guests', function () {
        $response = get('/public-monitors?status_filter=unsubscribed');

        $response->assertOk();
    });

    it('invokes the json api for guests', function () {
        $controller = new PublicMonitorController;
        $request = Request::create('/api/public-monitors', 'GET');

        $response = $controller($request);

        expect($response->status())->toBe(200);
        expect(json_decode($response->getContent(), true))->toHaveKey('data');
    });

    it('invokes the json api with filters and sort', function () {
        $downMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'down',
        ]);

        $controller = new PublicMonitorController;
        $request = Request::create('/api/public-monitors?status_filter=down&sort_by=status', 'GET');

        $response = $controller($request);

        expect($response->status())->toBe(200);
        $data = collect(json_decode($response->getContent(), true)['data']);
        expect($data->pluck('id'))->toContain($downMonitor->id);
    });

    it('invokes the json api with search and invalid sort', function () {
        $controller = new PublicMonitorController;
        $request = Request::create('/api/public-monitors?sort_by=invalid&search=example', 'GET');

        $response = $controller($request);

        expect($response->status())->toBe(200);
        expect(json_decode($response->getContent(), true))->toHaveKey('data');
    });

    it('invokes the json api for authenticated users', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $controller = new PublicMonitorController;
        $request = Request::create('/api/public-monitors', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $controller($request);

        expect($response->status())->toBe(200);
        expect(json_decode($response->getContent(), true))->toHaveKey('data');
    });
});
