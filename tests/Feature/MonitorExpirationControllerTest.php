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
});
