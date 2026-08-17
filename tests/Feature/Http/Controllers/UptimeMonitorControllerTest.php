<?php

use App\Models\Monitor;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('converts http urls to https when storing', function () {
    $response = $this->from('/monitors')
        ->post('/monitor', [
            'url' => 'http://example.com/',
            'uptime_check_interval' => 5,
        ]);

    $response->assertRedirect(route('monitor.index'));

    expect(Monitor::withoutGlobalScopes()
        ->where('url', 'https://example.com')
        ->exists())->toBeTrue();
});

it('attaches existing monitor to user instead of duplicating', function () {
    // Create a monitor owned by another user (auth guard off to avoid auto-attach)
    auth()->logout();
    $owner = User::factory()->create();
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
    $monitor->users()->sync([$owner->id => ['is_active' => true]]);
    auth()->login($this->user);

    $response = $this->from('/monitors')
        ->post('/monitor', [
            'url' => 'https://example.com',
            'uptime_check_interval' => 5,
        ]);

    $response->assertRedirect(route('monitor.index'));

    expect($monitor->users()->where('user_id', $this->user->id)->exists())->toBeTrue();
    expect(Monitor::withoutGlobalScopes()->count())->toBe(1);
});

it('stores a new monitor with tags', function () {
    $response = $this->from('/monitors')
        ->post('/monitor', [
            'url' => 'https://new-site.test',
            'uptime_check_interval' => 5,
            'tags' => ['laravel', 'production'],
        ]);

    $response->assertRedirect(route('monitor.index'));

    $monitor = Monitor::withoutGlobalScopes()
        ->where('url', 'https://new-site.test')
        ->first();

    expect($monitor)->not->toBeNull();
    expect($monitor->tags()->pluck('name')->all())->toContain('laravel');
});

it('updates a monitor and syncs tags', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_public' => false,
    ]);
    $monitor->users()->sync([$this->user->id => ['is_active' => true]]);

    $response = $this->from('/monitors')
        ->put("/monitor/{$monitor->id}", [
            'url' => 'https://example.com',
            'uptime_check_interval' => 10,
            'uptime_check_enabled' => true,
            'certificate_check_enabled' => true,
            'domain_expiration_check_enabled' => false,
            'is_public' => false,
            'tags' => ['updated-tag'],
        ]);

    $response->assertRedirect(route('monitor.index'));

    expect($monitor->fresh()->uptime_check_interval_in_minutes)->toBe(10);
    expect($monitor->fresh()->tags()->pluck('name')->all())->toContain('updated-tag');
});

it('updates http url to https when updating', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_public' => false,
    ]);
    $monitor->users()->sync([$this->user->id => ['is_active' => true]]);

    $this->from('/monitors')
        ->put("/monitor/{$monitor->id}", [
            'url' => 'http://example.com/',
            'uptime_check_interval' => 5,
            'uptime_check_enabled' => true,
            'certificate_check_enabled' => true,
            'domain_expiration_check_enabled' => false,
            'is_public' => false,
        ]);

    expect((string) $monitor->fresh()->url)->toBe('http://example.com');
});

it('destroys a monitor owned by the user', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_public' => false,
    ]);
    $monitor->users()->sync([$this->user->id => ['is_active' => true]]);

    $response = $this->from('/monitors')
        ->delete("/monitor/{$monitor->id}");

    $response->assertRedirect(route('monitor.index'));

    expect(Monitor::withoutGlobalScopes()->find($monitor->id))->toBeNull();
});

it('detaches a monitor not owned by the user', function () {
    auth()->logout();
    $owner = User::factory()->create();
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
    $monitor->users()->sync([$owner->id => ['is_active' => true]]);
    auth()->login($this->user);

    // Current user is subscribed (e.g. via a public monitor)
    $monitor->users()->attach($this->user->id, ['is_active' => true]);

    $response = $this->from('/monitors')
        ->delete("/monitor/{$monitor->id}");

    $response->assertRedirect(route('monitor.index'));

    expect($monitor->fresh())->not->toBeNull();
    expect($monitor->users()->where('user_id', $this->user->id)->exists())->toBeFalse();
});
