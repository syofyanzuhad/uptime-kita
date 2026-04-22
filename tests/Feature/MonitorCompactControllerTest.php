<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('compact monitor view is accessible to guests', function () {
    $this->get(route('monitor.compact'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('monitors/Compact')
        );
});

test('compact monitor view is accessible to authenticated users', function () {
    $user = User::factory()->create();
    \App\Models\Monitor::factory()->create(['uptime_check_enabled' => true]);

    actingAs($user)
        ->get(route('monitor.compact'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('monitors/Compact')
            ->has('monitors.data')
            ->has('availableTags')
            ->has('monitors.data.0', fn ($page) => $page
                ->has('certificate_status')
                ->has('certificate_expiration_date')
                ->etc()
            )
        );
});
