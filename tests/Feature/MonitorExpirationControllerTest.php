<?php

use App\Models\Monitor;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('MonitorExpirationController', function () {
    it('requires authentication', function () {
        auth()->logout();

        $this->get(route('monitors.expiration'))
            ->assertRedirect('/login');
    });

    it('displays only monitors with domain expiration checking enabled and a known date', function () {
        $tracked = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(60),
        ]);
        Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => null,
        ]);
        Monitor::factory()->create([
            'domain_expiration_check_enabled' => false,
            'domain_expiration_date' => now()->addDays(60),
        ]);

        $this->user->monitors()->syncWithoutDetaching([$tracked->id]);

        $this->get(route('monitors.expiration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('monitors/Expiration')
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $tracked->id)
                ->where('stats.total', 1)
            );
    });

    it('sorts monitors by soonest expiration date first', function () {
        $later = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(90),
        ]);
        $soonest = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(10),
        ]);

        $this->user->monitors()->syncWithoutDetaching([$later->id, $soonest->id]);

        $this->get(route('monitors.expiration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 2)
                ->where('monitors.data.0.id', $soonest->id)
                ->where('monitors.data.1.id', $later->id)
            );
    });

    it('does not leak other users monitors', function () {
        $myMonitor = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
        ]);
        $this->user->monitors()->syncWithoutDetaching([$myMonitor->id]);

        // Create another user's monitor while logged out to avoid the auto-attach hook
        auth()->logout();
        $otherMonitor = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
        ]);
        $otherUser = User::factory()->create();
        $otherUser->monitors()->syncWithoutDetaching([$otherMonitor->id]);
        $this->actingAs($this->user);

        $this->get(route('monitors.expiration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $myMonitor->id)
            );
    });

    it('reports expired and expiring soon counts in stats', function () {
        $expired = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->subDays(5),
        ]);
        $expiringSoon = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(10),
        ]);
        $farOut = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(120),
        ]);

        $this->user->monitors()->syncWithoutDetaching([$expired->id, $expiringSoon->id, $farOut->id]);

        $this->get(route('monitors.expiration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 3)
                ->where('stats.expired', 1)
                ->where('stats.expiring_soon', 1)
            );
    });

    it('includes a negative days_left for expired domains', function () {
        $expired = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->startOfDay()->subDays(3),
        ]);

        $this->user->monitors()->syncWithoutDetaching([$expired->id]);

        $this->get(route('monitors.expiration'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('monitors.data.0.days_left', -3)
            );
    });

    it('filters monitors by search query', function () {
        $alpha = Monitor::factory()->create([
            'url' => 'https://alpha-service.com',
            'display_name' => 'Alpha Service',
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(20),
        ]);
        $beta = Monitor::factory()->create([
            'url' => 'https://beta-platform.org',
            'display_name' => 'Beta Platform',
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(40),
        ]);

        $this->user->monitors()->syncWithoutDetaching([$alpha->id, $beta->id]);

        $this->get(route('monitors.expiration', ['search' => 'alpha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $alpha->id)
                ->where('search', 'alpha')
            );
    });

    it('filters monitors by expiration status', function () {
        $expired = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->subDays(2),
        ]);
        $expiringSoon = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(15),
        ]);
        $healthy = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(90),
        ]);

        $this->user->monitors()->syncWithoutDetaching([$expired->id, $expiringSoon->id, $healthy->id]);

        // Filter expired
        $this->get(route('monitors.expiration', ['status_filter' => 'expired']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $expired->id)
                ->where('statusFilter', 'expired')
            );

        // Filter expiring soon
        $this->get(route('monitors.expiration', ['status_filter' => 'expiring_soon']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $expiringSoon->id)
                ->where('statusFilter', 'expiring_soon')
            );

        // Filter healthy
        $this->get(route('monitors.expiration', ['status_filter' => 'healthy']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $healthy->id)
                ->where('statusFilter', 'healthy')
            );
    });

    it('filters monitors by uptime status', function () {
        $up = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
            'uptime_status' => 'up',
        ]);
        $down = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
            'uptime_status' => 'down',
        ]);

        $this->user->monitors()->syncWithoutDetaching([$up->id, $down->id]);

        $this->get(route('monitors.expiration', ['uptime_filter' => 'down']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $down->id)
                ->where('uptimeFilter', 'down')
            );
    });

    it('filters monitors by tag', function () {
        $monitorWithTag = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
        ]);
        $monitorWithoutTag = Monitor::factory()->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
        ]);

        $monitorWithTag->attachTag('production');

        $this->user->monitors()->syncWithoutDetaching([$monitorWithTag->id, $monitorWithoutTag->id]);

        $this->get(route('monitors.expiration', ['tag_filter' => 'production']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 1)
                ->where('monitors.data.0.id', $monitorWithTag->id)
                ->where('tagFilter', 'production')
            );
    });

    it('respects per_page parameter', function () {
        $monitors = Monitor::factory()->count(30)->create([
            'domain_expiration_check_enabled' => true,
            'domain_expiration_date' => now()->addDays(30),
        ]);

        $this->user->monitors()->syncWithoutDetaching($monitors->pluck('id'));

        $this->get(route('monitors.expiration', ['per_page' => 25]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('monitors.data', 25)
                ->where('monitors.meta.per_page', 25)
                ->where('monitors.meta.total', 30)
                ->where('perPage', 25)
            );
    });
});
