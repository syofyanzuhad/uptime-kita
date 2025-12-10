<?php

use App\Jobs\IncrementMonitorPageViewJob;
use App\Models\Monitor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\get;

beforeEach(function () {
    // Create a public monitor for testing
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'is_public' => true,
        'uptime_check_enabled' => true,
        'page_views_count' => 0,
    ]);
});

describe('Page View Job', function () {
    it('increments view count when job is processed', function () {
        $job = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.1');
        $job->handle();

        $this->monitor->refresh();
        expect($this->monitor->page_views_count)->toBe(1);
    });

    it('does not increment view count for same IP within cooldown period', function () {
        $job1 = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.1');
        $job1->handle();

        $job2 = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.1');
        $job2->handle();

        $this->monitor->refresh();
        expect($this->monitor->page_views_count)->toBe(1);
    });

    it('increments view count for different IPs', function () {
        $job1 = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.1');
        $job1->handle();

        $job2 = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.2');
        $job2->handle();

        $this->monitor->refresh();
        expect($this->monitor->page_views_count)->toBe(2);
    });

    it('allows increment after cooldown expires', function () {
        $job1 = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.1');
        $job1->handle();

        // Clear the cache to simulate cooldown expiry
        $cacheKey = 'monitor_view_'.$this->monitor->id.'_'.md5('192.168.1.1');
        Cache::forget($cacheKey);

        $job2 = new IncrementMonitorPageViewJob($this->monitor->id, '192.168.1.1');
        $job2->handle();

        $this->monitor->refresh();
        expect($this->monitor->page_views_count)->toBe(2);
    });
});

describe('Public Monitor Show Controller', function () {
    it('dispatches page view job when visiting public monitor page', function () {
        Queue::fake();

        $domain = parse_url($this->monitor->url, PHP_URL_HOST);
        get("/m/{$domain}");

        Queue::assertPushed(IncrementMonitorPageViewJob::class, function ($job) {
            return $job->monitorId === $this->monitor->id;
        });
    });

    it('does not dispatch job for non-existent monitors', function () {
        Queue::fake();

        get('/m/non-existent-domain.com');

        Queue::assertNotPushed(IncrementMonitorPageViewJob::class);
    });
});

describe('Monitor Model', function () {
    it('formats page views correctly for small numbers', function () {
        $this->monitor->update(['page_views_count' => 42]);
        expect($this->monitor->formatted_page_views)->toBe('42');
    });

    it('formats page views correctly for thousands', function () {
        $this->monitor->update(['page_views_count' => 1500]);
        expect($this->monitor->formatted_page_views)->toBe('1.5k');
    });

    it('formats page views correctly for millions', function () {
        $this->monitor->update(['page_views_count' => 2500000]);
        expect($this->monitor->formatted_page_views)->toBe('2.5M');
    });

    it('formats zero page views correctly', function () {
        expect($this->monitor->formatted_page_views)->toBe('0');
    });
});

describe('Monitor Resource', function () {
    it('includes page views in API response', function () {
        $this->monitor->update(['page_views_count' => 1234]);

        $domain = parse_url($this->monitor->url, PHP_URL_HOST);
        $response = get("/m/{$domain}");

        $response->assertInertia(fn ($page) => $page
            ->component('monitors/PublicShow')
            ->has('monitor.data.page_views_count')
            ->has('monitor.data.formatted_page_views')
            ->where('monitor.data.page_views_count', 1234)
            ->where('monitor.data.formatted_page_views', '1.2k')
        );
    });
});
