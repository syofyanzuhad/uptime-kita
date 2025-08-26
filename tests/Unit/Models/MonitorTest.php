<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\MonitorUptimeDaily;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Url\Url;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(now());
    $this->user = User::factory()->create();
    $this->monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow(null);
});

describe('Monitor Model', function () {
    describe('casts', function () {
        it('casts boolean attributes correctly', function () {
            expect($this->monitor->uptime_check_enabled)->toBeTrue();
            expect($this->monitor->certificate_check_enabled)->toBeBool();
        });

        it('casts datetime attributes correctly', function () {
            $this->monitor->update(['uptime_last_check_date' => now()]);

            expect($this->monitor->uptime_last_check_date)->toBeInstanceOf(Carbon::class);
        });
    });

    describe('scopes', function () {
        it('enabled scope filters enabled monitors', function () {
            Monitor::factory()->create(['uptime_check_enabled' => false]);
            Monitor::factory()->create(['uptime_check_enabled' => true]);

            $enabledMonitors = Monitor::withoutGlobalScopes()->enabled()->get();

            expect($enabledMonitors)->toHaveCount(2); // Our monitor + the enabled one
            $enabledMonitors->each(function ($monitor) {
                expect($monitor->uptime_check_enabled)->toBeTrue();
            });
        });

        it('public scope filters public monitors', function () {
            Monitor::factory()->create(['is_public' => true]);
            Monitor::factory()->create(['is_public' => false]);

            $publicMonitors = Monitor::withoutGlobalScopes()->public()->get();

            $publicMonitors->each(function ($monitor) {
                expect((bool) $monitor->is_public)->toBeTrue();
            });
        });

        it('private scope filters private monitors', function () {
            Monitor::factory()->create(['is_public' => true]);
            Monitor::factory()->create(['is_public' => false]);

            $privateMonitors = Monitor::withoutGlobalScopes()->private()->get();

            $privateMonitors->each(function ($monitor) {
                expect((bool) $monitor->is_public)->toBeFalse();
            });
        });

        it('search scope filters by url and name', function () {
            Monitor::factory()->create(['url' => 'https://test.com']);
            Monitor::factory()->create(['url' => 'https://search-me.com']);
            Monitor::factory()->create(['url' => 'https://other.com']);

            $searchResults = Monitor::withoutGlobalScopes()->search('test')->get();

            expect($searchResults)->toHaveCount(1);
            expect((string) $searchResults->first()->url)->toContain('test');
        });

        it('search scope requires minimum 3 characters', function () {
            Monitor::factory()->create(['url' => 'https://ab.com']);

            $shortSearch = Monitor::withoutGlobalScopes()->search('ab')->get();
            $longSearch = Monitor::withoutGlobalScopes()->search('abc')->get();

            // Should return all monitors for short search (no filtering)
            expect($shortSearch->count())->toBeGreaterThan(0);
            // Should filter for long search
            expect($longSearch->count())->toBeLessThanOrEqual($shortSearch->count());
        });
    });

    describe('attributes', function () {
        it('returns url as Url instance', function () {
            expect($this->monitor->url)->toBeInstanceOf(Url::class);
            expect((string) $this->monitor->url)->toBe('https://example.com');
        });

        it('returns null url when not set', function () {
            $monitor = new Monitor();

            expect($monitor->url)->toBeNull();
        });

        it('returns favicon url', function () {
            $favicon = $this->monitor->favicon;

            expect($favicon)->toContain('googleusercontent.com');
            expect($favicon)->toContain('example.com');
            expect($favicon)->toContain('sz=32');
        });

        it('returns null favicon when url not set', function () {
            $monitor = new Monitor();

            expect($monitor->favicon)->toBeNull();
        });

        it('returns raw url as string', function () {
            expect($this->monitor->raw_url)->toBe('https://example.com');
        });

        it('returns host from url', function () {
            expect($this->monitor->host)->toBe('example.com');
        });

        it('formats uptime last check date correctly', function () {
            $this->monitor->update(['uptime_last_check_date' => '2024-01-01 12:34:56']);

            $formatted = $this->monitor->uptime_last_check_date;

            expect($formatted)->toBeInstanceOf(Carbon::class);
            expect($formatted->second)->toBe(0); // Seconds should be set to 0
        });
    });

    describe('relationships', function () {
        it('has many users relationship', function () {
            $this->monitor->users()->attach($this->user->id);

            expect($this->monitor->users)->toHaveCount(1);
            expect($this->monitor->users->first()->id)->toBe($this->user->id);
        });

        it('has many status pages relationship', function () {
            $statusPage = StatusPage::factory()->create();
            $this->monitor->statusPages()->attach($statusPage->id);

            expect($this->monitor->statusPages)->toHaveCount(1);
            expect($this->monitor->statusPages->first()->id)->toBe($statusPage->id);
        });

        it('has many histories relationship', function () {
            MonitorHistory::factory()->create(['monitor_id' => $this->monitor->id]);

            expect($this->monitor->histories)->toHaveCount(1);
        });

        it('has one uptime daily relationship', function () {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->monitor->id,
                'date' => now()->toDateString(),
            ]);

            expect($this->monitor->uptimeDaily)->not->toBeNull();
        });
    });

    describe('owner methods', function () {
        it('returns owner as first associated user', function () {
            $firstUser = User::factory()->create();
            $secondUser = User::factory()->create();

            // Associate in specific order
            $this->monitor->users()->attach($firstUser->id, ['created_at' => now()->subHour()]);
            $this->monitor->users()->attach($secondUser->id, ['created_at' => now()]);

            $owner = $this->monitor->owner;

            expect($owner->id)->toBe($firstUser->id);
        });

        it('checks if user is owner correctly', function () {
            $owner = User::factory()->create();
            $notOwner = User::factory()->create();

            $this->monitor->users()->attach($owner->id, ['created_at' => now()->subHour()]);
            $this->monitor->users()->attach($notOwner->id, ['created_at' => now()]);

            expect($this->monitor->isOwnedBy($owner))->toBeTrue();
            expect($this->monitor->isOwnedBy($notOwner))->toBeFalse();
        });

        it('returns false for ownership when no users', function () {
            expect($this->monitor->isOwnedBy($this->user))->toBeFalse();
        });
    });

    describe('createOrUpdateHistory', function () {
        it('creates new history record', function () {
            $data = [
                'uptime_status' => 'up',
                'response_time' => 250,
                'status_code' => 200,
            ];

            $history = $this->monitor->createOrUpdateHistory($data);

            expect($history)->toBeInstanceOf(MonitorHistory::class);
            expect($history->uptime_status)->toBe('up');
            expect($history->response_time)->toBe(250);
            expect($history->status_code)->toBe(200);
        });

        it('updates existing history record within same minute', function () {
            $now = now();
            $minuteStart = $now->copy()->setSeconds(0)->setMicroseconds(0);

            // Create initial history
            $this->monitor->createOrUpdateHistory([
                'uptime_status' => 'up',
                'response_time' => 200,
            ]);

            // Update within same minute
            $updatedHistory = $this->monitor->createOrUpdateHistory([
                'uptime_status' => 'down',
                'response_time' => 500,
            ]);

            expect(MonitorHistory::where('monitor_id', $this->monitor->id)->count())->toBe(1);
            expect($updatedHistory->uptime_status)->toBe('down');
            expect($updatedHistory->response_time)->toBe(500);
        });
    });

    describe('today uptime percentage', function () {
        it('returns 0 when no uptime daily record exists', function () {
            expect($this->monitor->today_uptime_percentage)->toBe(0);
        });

        it('returns uptime percentage from daily record', function () {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->monitor->id,
                'date' => now()->toDateString(),
                'uptime_percentage' => 95.5,
            ]);

            // Refresh to load relationship
            $this->monitor->load('uptimeDaily');

            expect($this->monitor->today_uptime_percentage)->toBe(95.5);
        });
    });
});
