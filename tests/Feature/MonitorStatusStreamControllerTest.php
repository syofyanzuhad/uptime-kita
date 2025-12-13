<?php

use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\get;

describe('MonitorStatusStreamController', function () {
    beforeEach(function () {
        Cache::flush();
    });

    afterEach(function () {
        Cache::flush();
    });

    it('returns SSE response with correct content type header', function () {
        $response = get('/api/monitor-status-stream');

        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    });

    it('returns SSE response with cache control header', function () {
        $response = get('/api/monitor-status-stream');

        $response->assertHeader('Cache-Control');
    });

    it('accepts monitor_ids query parameter', function () {
        $response = get('/api/monitor-status-stream?monitor_ids=1,2,3');

        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    });

    it('accepts status_page_id query parameter', function () {
        $response = get('/api/monitor-status-stream?status_page_id=1');

        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    });

    it('accepts last_event_id query parameter', function () {
        $response = get('/api/monitor-status-stream?last_event_id=msc_123');

        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    });

    it('is rate limited', function () {
        // Make 10 requests (the limit)
        for ($i = 0; $i < 10; $i++) {
            get('/api/monitor-status-stream');
        }

        // The 11th request should be rate limited
        $response = get('/api/monitor-status-stream');

        $response->assertStatus(429);
    });
});
