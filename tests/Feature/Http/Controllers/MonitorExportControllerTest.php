<?php

use App\Models\Monitor;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
});

test('exports monitors to csv', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
    $this->user->monitors()->attach($monitor->id, [
        'is_active' => true,
        'is_pinned' => false,
    ]);

    $response = $this->actingAs($this->user)->get('/monitors/export/csv');

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    $response->assertHeader('content-disposition');
    expect($response->headers->get('content-disposition'))->toContain('.csv');
    expect($response->streamedContent())->toContain('https://example.com');
});

test('exports monitors to json', function () {
    $monitor = Monitor::factory()->create(['url' => 'https://example.com']);
    $this->user->monitors()->attach($monitor->id, [
        'is_active' => true,
        'is_pinned' => false,
    ]);

    $response = $this->actingAs($this->user)->get('/monitors/export/json');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/json');
    expect($response->headers->get('content-disposition'))->toContain('.json');
    expect($response->streamedContent())->toContain('https://example.com');
});

test('requires authentication for csv export', function () {
    $response = $this->get('/monitors/export/csv');

    $response->assertRedirect('/login');
});

test('requires authentication for json export', function () {
    $response = $this->get('/monitors/export/json');

    $response->assertRedirect('/login');
});
