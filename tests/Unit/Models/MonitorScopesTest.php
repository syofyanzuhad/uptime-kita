<?php

use App\Models\Monitor;

test('forPublicList scope selects proper columns and filters public monitors', function () {
    Monitor::factory()->create(['is_public' => true, 'url' => 'https://public-scope.com']);
    Monitor::factory()->create(['is_public' => false, 'url' => 'https://private-scope.com']);

    $results = Monitor::withoutGlobalScope('user')->forPublicList()->get();

    expect($results->every(fn ($m) => $m->is_public))->toBeTrue();
});

test('forUserList scope executes successfully', function () {
    Monitor::factory()->create(['is_public' => false, 'url' => 'https://user-scope.com']);

    $results = Monitor::withoutGlobalScope('user')->forUserList()->get();

    expect($results)->not->toBeEmpty();
});
