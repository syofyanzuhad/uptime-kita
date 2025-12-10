<?php

use App\Models\Monitor;
use App\Models\User;
use App\Models\UserMonitor;

describe('UserMonitor Model', function () {
    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create();

            $attributes = [
                'user_id' => $user->id,
                'monitor_id' => $monitor->id,
                'is_active' => true,
            ];

            $userMonitor = UserMonitor::create($attributes);

            expect($userMonitor->user_id)->toBe($user->id);
            expect($userMonitor->monitor_id)->toBe($monitor->id);
            expect($userMonitor->is_active)->toBe(true);
        });
    });

    describe('user relationship', function () {
        it('belongs to a user', function () {
            $user = User::factory()->create();
            $userMonitor = UserMonitor::factory()->create([
                'user_id' => $user->id,
            ]);

            expect($userMonitor->user)->toBeInstanceOf(User::class);
            expect($userMonitor->user->id)->toBe($user->id);
        });
    });

    describe('monitor relationship', function () {
        it('belongs to a monitor', function () {
            $monitor = Monitor::factory()->create();
            $userMonitor = UserMonitor::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            expect($userMonitor->monitor)->toBeInstanceOf(Monitor::class);
            expect($userMonitor->monitor->id)->toBe($monitor->id);
        });
    });

    describe('active scope', function () {
        it('returns only active user monitor relationships', function () {
            // Create active relationships
            $activeUserMonitor1 = UserMonitor::factory()->active()->create();
            $activeUserMonitor2 = UserMonitor::factory()->active()->create();

            // Create inactive relationship
            UserMonitor::factory()->inactive()->create();

            $activeRelationships = UserMonitor::active()->get();

            expect($activeRelationships)->toHaveCount(2);
            $activeIds = $activeRelationships->pluck('id')->toArray();
            expect($activeIds)->toContain($activeUserMonitor1->id);
            expect($activeIds)->toContain($activeUserMonitor2->id);
        });

        it('returns empty collection when no active relationships exist', function () {
            UserMonitor::factory()->count(3)->inactive()->create();

            $activeRelationships = UserMonitor::active()->get();

            expect($activeRelationships)->toHaveCount(0);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            $userMonitor = new UserMonitor;
            expect($userMonitor->getTable())->toBe('user_monitor');
        });

        it('extends Pivot class', function () {
            expect(UserMonitor::class)->toExtend(\Illuminate\Database\Eloquent\Relations\Pivot::class);
        });

        it('handles timestamps', function () {
            $userMonitor = UserMonitor::factory()->create();

            expect($userMonitor->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
            expect($userMonitor->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });
    });

    describe('pivot table functionality', function () {
        it('connects users and monitors', function () {
            $user = User::factory()->create();
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            // Create pivot records
            UserMonitor::create([
                'user_id' => $user->id,
                'monitor_id' => $monitor1->id,
                'is_active' => true,
            ]);

            UserMonitor::create([
                'user_id' => $user->id,
                'monitor_id' => $monitor2->id,
                'is_active' => true,
            ]);

            // Verify relationships exist
            $userMonitors = UserMonitor::where('user_id', $user->id)->get();

            expect($userMonitors)->toHaveCount(2);
            expect($userMonitors->pluck('monitor_id')->toArray())->toContain($monitor1->id, $monitor2->id);
        });

        it('can have multiple users for one monitor', function () {
            $monitor = Monitor::factory()->create();
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            UserMonitor::create([
                'user_id' => $user1->id,
                'monitor_id' => $monitor->id,
                'is_active' => true,
            ]);

            UserMonitor::create([
                'user_id' => $user2->id,
                'monitor_id' => $monitor->id,
                'is_active' => true,
            ]);

            $monitorUsers = UserMonitor::where('monitor_id', $monitor->id)->get();

            expect($monitorUsers)->toHaveCount(2);
            expect($monitorUsers->pluck('user_id')->toArray())->toContain($user1->id, $user2->id);
        });
    });

    describe('active status handling', function () {
        it('can store true is_active status', function () {
            $userMonitor = UserMonitor::factory()->active()->create();

            expect($userMonitor->is_active)->toBe(true);
        });

        it('can store false is_active status', function () {
            $userMonitor = UserMonitor::factory()->inactive()->create();

            expect($userMonitor->is_active)->toBe(false);
        });

        it('can toggle active status', function () {
            $userMonitor = UserMonitor::factory()->active()->create();

            expect($userMonitor->is_active)->toBe(true);

            // Toggle to false
            $userMonitor->update(['is_active' => false]);
            $userMonitor->refresh();

            expect($userMonitor->is_active)->toBe(false);

            // Toggle back to true
            $userMonitor->update(['is_active' => true]);
            $userMonitor->refresh();

            expect($userMonitor->is_active)->toBe(true);
        });
    });

    describe('data integrity', function () {
        it('maintains referential integrity with users', function () {
            $user = User::factory()->create();
            $userMonitor = UserMonitor::factory()->create([
                'user_id' => $user->id,
            ]);

            // Verify the user still exists and relationship works
            $userMonitor = $userMonitor->fresh();
            expect($userMonitor->user)->not->toBeNull();
            expect($userMonitor->user->id)->toBe($user->id);
        });

        it('maintains referential integrity with monitors', function () {
            $monitor = Monitor::factory()->create();
            $userMonitor = UserMonitor::factory()->create([
                'monitor_id' => $monitor->id,
            ]);

            // Verify the monitor still exists and relationship works
            $userMonitor = $userMonitor->fresh();
            expect($userMonitor->monitor)->not->toBeNull();
            expect($userMonitor->monitor->id)->toBe($monitor->id);
        });
    });

    describe('subscription management', function () {
        it('can manage user subscriptions to monitors', function () {
            $user = User::factory()->create();
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();
            $monitor3 = Monitor::factory()->create();

            // User subscribes to monitor1 (active)
            UserMonitor::create([
                'user_id' => $user->id,
                'monitor_id' => $monitor1->id,
                'is_active' => true,
            ]);

            // User subscribes to monitor2 but then deactivates
            UserMonitor::create([
                'user_id' => $user->id,
                'monitor_id' => $monitor2->id,
                'is_active' => false,
            ]);

            // User subscribes to monitor3 (active)
            UserMonitor::create([
                'user_id' => $user->id,
                'monitor_id' => $monitor3->id,
                'is_active' => true,
            ]);

            $activeSubscriptions = UserMonitor::where('user_id', $user->id)->active()->get();
            $allSubscriptions = UserMonitor::where('user_id', $user->id)->get();

            expect($activeSubscriptions)->toHaveCount(2);
            expect($allSubscriptions)->toHaveCount(3);
            expect($activeSubscriptions->pluck('monitor_id')->toArray())->toContain($monitor1->id, $monitor3->id);
        });
    });
});
