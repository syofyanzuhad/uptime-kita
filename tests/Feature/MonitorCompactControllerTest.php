<?php

use App\Models\Monitor;
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
    
    actingAs($user)
        ->get(route('monitor.compact'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('monitors/Compact')
            ->has('monitors.data')
            ->has('availableTags')
        );
});
