<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitorCompactControllerCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_caches_monitor_list_results()
    {
        $user = User::factory()->create();
        Monitor::factory()->create(['is_public' => true, 'uptime_check_enabled' => true]);

        // Spy on the Cache facade
        Cache::spy();

        $this->actingAs($user)->get('/monitors');

        // Verify that Cache::remember was called with a key starting with 'monitors_compact_'
        Cache::shouldHaveReceived('remember')
            ->with(
                \Mockery::pattern('/^monitors_compact_/'),
                \Mockery::any(),
                \Mockery::any()
            );
    }

    public function test_it_uses_different_cache_keys_for_different_search_terms()
    {
        $user = User::factory()->create();
        Cache::spy();
        
        // Request 1
        $this->actingAs($user)->get('/monitors?search=site1');
        
        // Request 2 with different search
        $this->actingAs($user)->get('/monitors?search=site2');

        // Verify it was called at least twice (once for data, once for tags per request)
        Cache::shouldHaveReceived('remember')->atLeast()->twice();
    }
}
