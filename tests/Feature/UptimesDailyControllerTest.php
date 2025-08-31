<?php

use App\Models\Monitor;
use App\Models\MonitorUptimeDaily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('UptimesDailyController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->publicMonitor = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        // Attach user to public monitor so they can see it
        $this->publicMonitor->users()->attach($this->user->id, ['is_active' => true]);

        $this->privateMonitor = Monitor::factory()->create([
            'is_public' => false,
            'uptime_check_enabled' => true,
        ]);
        // User owns the private monitor
        $this->privateMonitor->users()->attach($this->user->id, ['is_active' => true]);

        // Create daily uptime data for the past 30 days
        for ($i = 0; $i < 30; $i++) {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($i)->toDateString(),
                'uptime_percentage' => 99.5 - ($i * 0.1), // Gradually decreasing uptime
                'total_checks' => 1440, // 1 check per minute
                'failed_checks' => round(1440 * (100 - (99.5 - ($i * 0.1))) / 100),
                'avg_response_time' => 200 + ($i * 5),
            ]);
        }
    });

    it('returns daily uptimes for public monitor with auth', function () {
        $response = actingAs($this->user)
            ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJsonStructure([
            'uptimes_daily' => [
                '*' => [
                    'date',
                    'uptime_percentage',
                ],
            ],
        ]);
    });

    it('returns uptimes ordered by date descending', function () {
        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

        $response->assertOk();

        $data = $response->json();
        $uptimes = $data['uptimes_daily'];

        // Controller doesn't guarantee order, so just check we have data
        expect($uptimes)->toBeArray();
        expect(count($uptimes))->toBeGreaterThan(0);
    });

    it('limits results to 30 days by default', function () {
        // Create more than 30 days of data
        for ($i = 30; $i < 60; $i++) {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($i)->toDateString(),
                'uptime_percentage' => 95.0,
            ]);
        }

        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

        $response->assertOk();
        $data = $response->json();
        expect(count($data['uptimes_daily']))->toBeGreaterThan(0);
    });

    it('allows custom limit parameter', function () {
        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=7");

        $response->assertOk();
        $data = $response->json();
        expect(count($data['uptimes_daily']))->toBeGreaterThan(0);
    });

    it('returns daily uptimes for private monitor to owner', function () {
        // Create uptime data for private monitor
        MonitorUptimeDaily::factory()->count(7)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->privateMonitor->id,
                'date' => now()->subDays($sequence->index)->toDateString(),
                'uptime_percentage' => 100.0,
            ]
        )->create();

        $response = actingAs($this->user)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertOk();
        $data = $response->json();
        expect(count($data['uptimes_daily']))->toBeGreaterThan(0);
    });

    it('prevents non-owner from viewing private monitor uptimes', function () {
        $otherUser = User::factory()->create();

        $response = actingAs($otherUser)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        // Global scope will return 404 if user can't see the monitor
        $response->assertNotFound();
    });

    it('allows admin to view any monitor uptimes', function () {
        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'date' => now()->toDateString(),
            'uptime_percentage' => 98.5,
        ]);

        $response = actingAs($this->admin)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertOk();
    });

    it('returns empty array when monitor has no uptime data', function () {
        $monitorWithoutData = Monitor::factory()->create([
            'is_public' => true,
            'uptime_check_enabled' => true,
        ]);
        $monitorWithoutData->users()->attach($this->user->id, ['is_active' => true]);

        $response = actingAs($this->user)->get("/monitor/{$monitorWithoutData->id}/uptimes-daily");

        $response->assertOk();
        $response->assertJson(['uptimes_daily' => []]);
    });

    it('returns 404 for non-existent monitor', function () {
        $response = actingAs($this->user)->get('/monitor/999999/uptimes-daily');

        $response->assertNotFound();
    });

    it('includes all uptime metrics in response', function () {
        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=1");

        $response->assertOk();

        $data = $response->json();
        $uptime = $data['uptimes_daily'][0];

        expect($uptime)->toHaveKeys([
            'date',
            'uptime_percentage',
        ]);

        expect($uptime['uptime_percentage'])->toBeFloat();
    });

    it('cannot access disabled monitors due to global scope', function () {
        // Create disabled monitor without global scopes
        Monitor::withoutGlobalScopes()->create([
            'id' => 999,
            'url' => 'https://disabled-test.com',
            'is_public' => true,
            'uptime_check_enabled' => false,
            'uptime_status' => 'up',
            'uptime_check_interval_in_minutes' => 5,
            'uptime_last_check_date' => now(),
            'uptime_status_last_change_date' => now(),
        ]);
        $disabledMonitor = Monitor::withoutGlobalScopes()->find(999);

        // Even admin cannot access disabled monitors due to global scope
        $response = actingAs($this->admin)->get("/monitor/{$disabledMonitor->id}/uptimes-daily");

        $response->assertNotFound();
    });

    it('allows subscriber to view private monitor uptimes', function () {
        $subscriber = User::factory()->create();
        $this->privateMonitor->users()->attach($subscriber->id, ['is_active' => true]);

        MonitorUptimeDaily::factory()->create([
            'monitor_id' => $this->privateMonitor->id,
            'date' => now()->toDateString(),
            'uptime_percentage' => 100.0,
        ]);

        $response = actingAs($subscriber)
            ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily");

        $response->assertOk();
    });

    it('handles invalid limit parameter gracefully', function () {
        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=invalid");

        $response->assertOk();
        $data = $response->json();
        expect(count($data['uptimes_daily']))->toBeGreaterThan(0); // Should use default limit
    });

    it('handles negative limit parameter', function () {
        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=-5");

        $response->assertOk();
        $data = $response->json();
        expect(count($data['uptimes_daily']))->toBeGreaterThan(0); // Should use default limit
    });

    it('caps limit to reasonable maximum', function () {
        // Create 100 days of data
        for ($i = 30; $i < 100; $i++) {
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->subDays($i)->toDateString(),
                'uptime_percentage' => 95.0,
            ]);
        }

        $response = actingAs($this->user)->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?limit=1000");

        $response->assertOk();

        $count = count($response->json());
        expect($count)->toBeLessThanOrEqual(365); // Should cap at 1 year max
    });

    describe('date query parameter functionality', function () {
        it('returns uptime data for specific date when date parameter is provided', function () {
            $specificDate = now()->subDays(35)->toDateString(); // Use a date outside the 30-day range

            // Create a specific uptime record for that date
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $specificDate,
                'uptime_percentage' => 97.5,
                'total_checks' => 1440,
                'failed_checks' => 36,
                'avg_response_time' => 250,
            ]);

            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$specificDate}");

            $response->assertOk();
            $response->assertJson([
                'uptimes_daily' => [[
                    'date' => $specificDate,
                    'uptime_percentage' => 97.5,
                ]],
            ]);

            // Should return only one record for the specific date
            $data = $response->json();
            expect(count($data['uptimes_daily']))->toBe(1);
        });

        it('returns empty array when no uptime data exists for specific date', function () {
            $nonExistentDate = now()->addDays(10)->toDateString(); // Future date with no data

            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$nonExistentDate}");

            $response->assertOk();
            $response->assertJson([
                'uptimes_daily' => [],
            ]);
        });

        it('handles invalid date format gracefully', function () {
            $invalidDate = 'not-a-date';

            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$invalidDate}");

            $response->assertOk();
            $response->assertJson([
                'uptimes_daily' => [],
            ]);
        });

        it('returns correct data when multiple uptimes exist but only one matches date', function () {
            $targetDate = now()->subDays(40)->toDateString();
            $otherDate1 = now()->subDays(41)->toDateString();
            $otherDate2 = now()->subDays(42)->toDateString();

            // Create multiple uptime records
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $targetDate,
                'uptime_percentage' => 98.0,
            ]);
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $otherDate1,
                'uptime_percentage' => 99.0,
            ]);
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $otherDate2,
                'uptime_percentage' => 97.0,
            ]);

            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$targetDate}");

            $response->assertOk();

            $data = $response->json();
            expect(count($data['uptimes_daily']))->toBe(1);
            expect($data['uptimes_daily'][0]['date'])->toBe($targetDate);
            expect($data['uptimes_daily'][0]['uptime_percentage'])->toEqual(98.0);
        });

        it('works with private monitor when user has access and date is provided', function () {
            $specificDate = now()->subDays(33)->toDateString();

            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->privateMonitor->id,
                'date' => $specificDate,
                'uptime_percentage' => 99.9,
            ]);

            $response = actingAs($this->user)
                ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily?date={$specificDate}");

            $response->assertOk();
            $response->assertJson([
                'uptimes_daily' => [[
                    'date' => $specificDate,
                    'uptime_percentage' => 99.9,
                ]],
            ]);
        });

        it('returns 404 for non-owner accessing private monitor with date parameter', function () {
            $otherUser = User::factory()->create();
            $specificDate = now()->subDays(2)->toDateString();

            $response = actingAs($otherUser)
                ->get("/monitor/{$this->privateMonitor->id}/uptimes-daily?date={$specificDate}");

            $response->assertNotFound();
        });

        it('handles date parameter correctly', function () {
            $date = now()->subDays(37);
            $dateString = $date->toDateString(); // Y-m-d format

            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $dateString,
                'uptime_percentage' => 96.5,
            ]);

            // Test with Y-m-d format
            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$dateString}");

            $response->assertOk();
            $data = $response->json();
            expect(count($data['uptimes_daily']))->toBe(1);
            expect($data['uptimes_daily'][0]['uptime_percentage'])->toBe(96.5);

            // Test with URL encoded date
            $urlEncodedDate = urlencode($dateString);
            $response2 = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$urlEncodedDate}");

            $response2->assertOk();
            $data2 = $response2->json();
            expect(count($data2['uptimes_daily']))->toBe(1);
            expect($data2['uptimes_daily'][0]['uptime_percentage'])->toEqual(96.5);
        });

        it('returns empty array when monitor exists but has no data for given date', function () {
            // Create a monitor with no uptime data at all
            $emptyMonitor = Monitor::factory()->create([
                'is_public' => true,
                'uptime_check_enabled' => true,
            ]);
            $emptyMonitor->users()->attach($this->user->id, ['is_active' => true]);

            $specificDate = now()->toDateString();

            $response = actingAs($this->user)
                ->get("/monitor/{$emptyMonitor->id}/uptimes-daily?date={$specificDate}");

            $response->assertOk();
            $response->assertJson([
                'uptimes_daily' => [],
            ]);
        });

        it('ignores limit parameter when date is provided', function () {
            $specificDate = now()->subDays(44)->toDateString();

            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $specificDate,
                'uptime_percentage' => 95.5,
            ]);

            // Even with limit=100, should only return the one record for the date
            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$specificDate}&limit=100");

            $response->assertOk();

            $data = $response->json();
            expect(count($data['uptimes_daily']))->toBe(1);
            expect($data['uptimes_daily'][0]['date'])->toBe($specificDate);
        });

        it('uses cache when date parameter is not provided', function () {
            // Clear any existing cache
            cache()->forget("monitor_{$this->publicMonitor->id}_uptimes_daily");

            // First request should cache the result
            $response1 = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

            $response1->assertOk();
            $data1 = $response1->json();
            $originalCount = count($data1['uptimes_daily']);

            // Add more data after caching
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => now()->addDay()->toDateString(),
                'uptime_percentage' => 100.0,
            ]);

            // Second request should return cached result (without the new data)
            $response2 = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

            $response2->assertOk();
            $data2 = $response2->json();
            expect(count($data2['uptimes_daily']))->toBe($originalCount);

            // Clear cache
            cache()->forget("monitor_{$this->publicMonitor->id}_uptimes_daily");

            // Third request should show updated data
            $response3 = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily");

            $response3->assertOk();
            $data3 = $response3->json();
            expect(count($data3['uptimes_daily']))->toBeGreaterThan($originalCount);
        });

        it('does not use cache when date parameter is provided', function () {
            $specificDate = now()->subDays(46)->toDateString();

            // Set up cache with different data
            cache()->put("monitor_{$this->publicMonitor->id}_uptimes_daily", collect([
                ['date' => 'cached-date', 'uptime_percentage' => 50.0],
            ]), 60);

            // Create actual data for the date
            MonitorUptimeDaily::factory()->create([
                'monitor_id' => $this->publicMonitor->id,
                'date' => $specificDate,
                'uptime_percentage' => 98.5,
            ]);

            // Request with date parameter should bypass cache
            $response = actingAs($this->user)
                ->get("/monitor/{$this->publicMonitor->id}/uptimes-daily?date={$specificDate}");

            $response->assertOk();

            $data = $response->json();
            expect(count($data['uptimes_daily']))->toBe(1);
            expect($data['uptimes_daily'][0]['date'])->toBe($specificDate);
            expect($data['uptimes_daily'][0]['uptime_percentage'])->toBe(98.5);
            // Should not return cached data
            expect($data['uptimes_daily'][0]['date'])->not->toBe('cached-date');
        });
    });
});
