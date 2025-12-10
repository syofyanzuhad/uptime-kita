<?php

use App\Models\Monitor;
use App\Models\User;
use App\Policies\MonitorPolicy;

beforeEach(function () {
    $this->policy = new MonitorPolicy;
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->monitor = Monitor::factory()->create(['is_public' => false]);
});

describe('MonitorPolicy', function () {
    describe('viewAny', function () {
        it('allows any authenticated user to view any monitors', function () {
            $result = $this->policy->viewAny($this->user);

            expect($result)->toBeTrue();
        });

        it('allows admin to view any monitors', function () {
            $result = $this->policy->viewAny($this->admin);

            expect($result)->toBeTrue();
        });
    });

    describe('view', function () {
        it('allows user to view monitor they are subscribed to', function () {
            // Associate user with monitor
            $this->monitor->users()->attach($this->user->id);

            $result = $this->policy->view($this->user, $this->monitor);

            expect($result)->toBeTrue();
        });

        it('denies user to view monitor they are not subscribed to', function () {
            // User is not associated with monitor
            $result = $this->policy->view($this->user, $this->monitor);

            expect($result)->toBeFalse();
        });

        it('allows admin to view any monitor if subscribed', function () {
            $this->monitor->users()->attach($this->admin->id);

            $result = $this->policy->view($this->admin, $this->monitor);

            expect($result)->toBeTrue();
        });
    });

    describe('create', function () {
        it('allows any authenticated user to create monitors', function () {
            $result = $this->policy->create($this->user);

            expect($result)->toBeTrue();
        });

        it('allows admin to create monitors', function () {
            $result = $this->policy->create($this->admin);

            expect($result)->toBeTrue();
        });
    });

    describe('update', function () {
        it('allows admin to update any monitor', function () {
            $result = $this->policy->update($this->admin, $this->monitor);

            expect($result)->toBeTrue();
        });

        it('denies regular user to update public monitor', function () {
            $publicMonitor = Monitor::factory()->create(['is_public' => true]);

            $result = $this->policy->update($this->user, $publicMonitor);

            expect($result)->toBeFalse();
        });

        it('allows owner to update private monitor', function () {
            // Mock the isOwnedBy method since it requires specific implementation
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('getAttribute')->with('is_public')->andReturn(false);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(true);

            $result = $this->policy->update($this->user, $monitor);

            expect($result)->toBeTrue();
        });

        it('denies non-owner to update private monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('getAttribute')->with('is_public')->andReturn(false);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(false);

            $result = $this->policy->update($this->user, $monitor);

            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows admin to delete any monitor', function () {
            $result = $this->policy->delete($this->admin, $this->monitor);

            expect($result)->toBeTrue();
        });

        it('denies regular user to delete public monitor', function () {
            $publicMonitor = Monitor::factory()->create(['is_public' => true]);

            $result = $this->policy->delete($this->user, $publicMonitor);

            expect($result)->toBeFalse();
        });

        it('allows owner to delete private monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('getAttribute')->with('is_public')->andReturn(false);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(true);

            $result = $this->policy->delete($this->user, $monitor);

            expect($result)->toBeTrue();
        });

        it('denies non-owner to delete private monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('getAttribute')->with('is_public')->andReturn(false);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(false);

            $result = $this->policy->delete($this->user, $monitor);

            expect($result)->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows admin to restore any monitor', function () {
            $result = $this->policy->restore($this->admin, $this->monitor);

            expect($result)->toBeTrue();
        });

        it('allows owner to restore their monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(true);

            $result = $this->policy->restore($this->user, $monitor);

            expect($result)->toBeTrue();
        });

        it('denies non-owner to restore monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(false);

            $result = $this->policy->restore($this->user, $monitor);

            expect($result)->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows admin to force delete any monitor', function () {
            $result = $this->policy->forceDelete($this->admin, $this->monitor);

            expect($result)->toBeTrue();
        });

        it('allows owner to force delete their monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(true);

            $result = $this->policy->forceDelete($this->user, $monitor);

            expect($result)->toBeTrue();
        });

        it('denies non-owner to force delete monitor', function () {
            $monitor = mock(Monitor::class);
            $monitor->shouldReceive('isOwnedBy')->with($this->user)->andReturn(false);

            $result = $this->policy->forceDelete($this->user, $monitor);

            expect($result)->toBeFalse();
        });
    });
});
