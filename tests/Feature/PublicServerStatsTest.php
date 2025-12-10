<?php

use function Pest\Laravel\get;

describe('Public Server Stats API', function () {
    it('returns server stats when enabled', function () {
        config(['app.show_public_server_stats' => true]);

        $response = get('/api/server-stats');

        $response->assertOk()
            ->assertJsonStructure([
                'enabled',
                'cpu_percent',
                'memory_percent',
                'uptime',
                'uptime_seconds',
                'response_time',
                'timestamp',
            ])
            ->assertJson(['enabled' => true]);
    });

    it('returns disabled response when feature is disabled', function () {
        config(['app.show_public_server_stats' => false]);

        $response = get('/api/server-stats');

        $response->assertOk()
            ->assertJson(['enabled' => false])
            ->assertJsonMissing(['cpu_percent']);
    });

    it('is rate limited', function () {
        config(['app.show_public_server_stats' => true]);

        // Make 30 requests (the limit)
        for ($i = 0; $i < 30; $i++) {
            get('/api/server-stats')->assertOk();
        }

        // The 31st request should be rate limited
        get('/api/server-stats')->assertStatus(429);
    });
});
