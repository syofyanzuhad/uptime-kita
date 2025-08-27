<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\StatusPageMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StatusPageMonitor Model', function () {
    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $statusPage = StatusPage::factory()->create();
            $monitor = Monitor::factory()->create();

            $attributes = [
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor->id,
                'order' => 5,
            ];

            $statusPageMonitor = StatusPageMonitor::create($attributes);

            expect($statusPageMonitor->status_page_id)->toBe($statusPage->id);
            expect($statusPageMonitor->monitor_id)->toBe($monitor->id);
            expect($statusPageMonitor->order)->toBe(5);
        });
    });

    describe('status page relationship', function () {
        it('belongs to a status page', function () {
            $statusPage = StatusPage::factory()->create();
            $statusPageMonitor = StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
            ]);

            expect($statusPageMonitor->statusPage)->toBeInstanceOf(StatusPage::class);
            expect($statusPageMonitor->statusPage->id)->toBe($statusPage->id);
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            $monitor = Monitor::factory()->create();
            $statusPageMonitor = StatusPageMonitor::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($statusPageMonitor->monitor)->toBeInstanceOf(Monitor::class);
            expect($statusPageMonitor->monitor->id)->toBe($monitor->id);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            $statusPageMonitor = new StatusPageMonitor;
            expect($statusPageMonitor->getTable())->toBe('status_page_monitor');
        });

        it('handles timestamps', function () {
            $statusPageMonitor = StatusPageMonitor::factory()->create();

            expect($statusPageMonitor->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($statusPageMonitor->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('ordering functionality', function () {
        it('can store different order values', function () {
            $statusPageMonitor1 = StatusPageMonitor::factory()->create(['order' => 1]);
            $statusPageMonitor2 = StatusPageMonitor::factory()->create(['order' => 5]);
            $statusPageMonitor3 = StatusPageMonitor::factory()->create(['order' => 10]);

            expect($statusPageMonitor1->order)->toBe(1);
            expect($statusPageMonitor2->order)->toBe(5);
            expect($statusPageMonitor3->order)->toBe(10);
        });

        it('can handle zero and negative order values', function () {
            $statusPageMonitor1 = StatusPageMonitor::factory()->create(['order' => 0]);
            $statusPageMonitor2 = StatusPageMonitor::factory()->create(['order' => -1]);

            expect($statusPageMonitor1->order)->toBe(0);
            expect($statusPageMonitor2->order)->toBe(-1);
        });

        it('can handle large order values', function () {
            $statusPageMonitor = StatusPageMonitor::factory()->create(['order' => 999999]);

            expect($statusPageMonitor->order)->toBe(999999);
        });
    });

    describe('pivot table functionality', function () {
        it('connects status pages and monitors', function () {
            $statusPage = StatusPage::factory()->create();
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            StatusPageMonitor::create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor1->id,
                'order' => 1,
            ]);

            StatusPageMonitor::create([
                'status_page_id' => $statusPage->id,
                'monitor_id' => $monitor2->id,
                'order' => 2,
            ]);

            $statusPageMonitors = StatusPageMonitor::where('status_page_id', $statusPage->id)->get();

            expect($statusPageMonitors)->toHaveCount(2);
            expect($statusPageMonitors->pluck('monitor_id')->toArray())->toContain($monitor1->id, $monitor2->id);
        });

        it('can have multiple status pages for one monitor', function () {
            $monitor = Monitor::factory()->create();
            $statusPage1 = StatusPage::factory()->create();
            $statusPage2 = StatusPage::factory()->create();

            StatusPageMonitor::create([
                'status_page_id' => $statusPage1->id,
                'monitor_id' => $monitor->id,
                'order' => 1,
            ]);

            StatusPageMonitor::create([
                'status_page_id' => $statusPage2->id,
                'monitor_id' => $monitor->id,
                'order' => 1,
            ]);

            $monitorStatusPages = StatusPageMonitor::where('monitor_id', $monitor->id)->get();

            expect($monitorStatusPages)->toHaveCount(2);
            expect($monitorStatusPages->pluck('status_page_id')->toArray())->toContain($statusPage1->id, $statusPage2->id);
        });
    });

    describe('data integrity', function () {
        it('maintains referential integrity with status pages', function () {
            $statusPage = StatusPage::factory()->create();
            $statusPageMonitor = StatusPageMonitor::factory()->create([
                'status_page_id' => $statusPage->id,
            ]);

            // Verify the status page still exists and relationship works
            $statusPageMonitor = $statusPageMonitor->fresh();
            expect($statusPageMonitor->statusPage)->not->toBeNull();
            expect($statusPageMonitor->statusPage->id)->toBe($statusPage->id);
        });

        it('maintains referential integrity with monitors', function () {
            $monitor = Monitor::factory()->create();
            $statusPageMonitor = StatusPageMonitor::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            // Verify the monitor still exists and relationship works
            $statusPageMonitor = $statusPageMonitor->fresh();
            expect($statusPageMonitor->monitor)->not->toBeNull();
            expect($statusPageMonitor->monitor->id)->toBe($monitor->id);
        });
    });
});
