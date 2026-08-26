<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

describe('MonitorCompactControllerCache', function () {
    it('caches monitor list results', function () {
        $user = User::factory()->create();
        Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

        Cache::spy();

        $this->actingAs($user)->get('/monitors');

        Cache::shouldHaveReceived('remember')
            ->with(
                Mockery::pattern('/^monitors_compact_/'),
                Mockery::any(),
                Mockery::any()
            );
    });

    it('uses different cache keys for different search terms', function () {
        $user = User::factory()->create();
        Cache::spy();

        $this->actingAs($user)->get('/monitors?search=site1');
        $this->actingAs($user)->get('/monitors?search=site2');

        Cache::shouldHaveReceived('remember')->atLeast()->twice();
    });
});
