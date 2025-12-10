<?php

use App\Models\User;
use App\Policies\UserPolicy;

describe('UserPolicy', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy;
        $this->regularUser = User::factory()->create(['is_admin' => false]);
        $this->adminUser = User::factory()->create(['is_admin' => true]);
    });

    describe('before', function () {
        it('grants all abilities to admin users', function () {
            expect($this->policy->before($this->adminUser, 'viewAny'))->toBeTrue();
            expect($this->policy->before($this->adminUser, 'view'))->toBeTrue();
            expect($this->policy->before($this->adminUser, 'create'))->toBeTrue();
            expect($this->policy->before($this->adminUser, 'update'))->toBeTrue();
            expect($this->policy->before($this->adminUser, 'delete'))->toBeTrue();
            expect($this->policy->before($this->adminUser, 'customAbility'))->toBeTrue();
        });

        it('returns null for regular users allowing policy methods to continue', function () {
            expect($this->policy->before($this->regularUser, 'viewAny'))->toBeNull();
            expect($this->policy->before($this->regularUser, 'view'))->toBeNull();
            expect($this->policy->before($this->regularUser, 'create'))->toBeNull();
            expect($this->policy->before($this->regularUser, 'update'))->toBeNull();
            expect($this->policy->before($this->regularUser, 'delete'))->toBeNull();
        });
    });

    describe('viewAny', function () {
        it('denies regular users from viewing any users', function () {
            expect($this->policy->viewAny($this->regularUser))->toBeFalse();
        });

        it('allows admin users through before method', function () {
            // The before method should grant access before viewAny is called
            expect($this->policy->before($this->adminUser, 'viewAny'))->toBeTrue();
        });
    });

    describe('view', function () {
        it('denies regular users from viewing users', function () {
            expect($this->policy->view($this->regularUser))->toBeFalse();
        });

        it('allows admin users through before method', function () {
            // The before method should grant access before view is called
            expect($this->policy->before($this->adminUser, 'view'))->toBeTrue();
        });
    });

    describe('create', function () {
        it('denies regular users from creating users', function () {
            expect($this->policy->create($this->regularUser))->toBeFalse();
        });

        it('allows admin users through before method', function () {
            // The before method should grant access before create is called
            expect($this->policy->before($this->adminUser, 'create'))->toBeTrue();
        });
    });

    describe('update', function () {
        it('denies regular users from updating users', function () {
            expect($this->policy->update($this->regularUser))->toBeFalse();
        });

        it('allows admin users through before method', function () {
            // The before method should grant access before update is called
            expect($this->policy->before($this->adminUser, 'update'))->toBeTrue();
        });
    });

    describe('delete', function () {
        it('denies regular users from deleting users', function () {
            expect($this->policy->delete($this->regularUser))->toBeFalse();
        });

        it('allows admin users through before method', function () {
            // The before method should grant access before delete is called
            expect($this->policy->before($this->adminUser, 'delete'))->toBeTrue();
        });
    });

    describe('admin privilege validation', function () {
        it('consistently applies admin privileges across all actions', function () {
            $abilities = ['viewAny', 'view', 'create', 'update', 'delete'];

            foreach ($abilities as $ability) {
                // Admin should always be granted access via before method
                expect($this->policy->before($this->adminUser, $ability))
                    ->toBeTrue("Admin should have access to {$ability}");

                // Regular user should continue to specific policy method
                expect($this->policy->before($this->regularUser, $ability))
                    ->toBeNull("Regular user should not get early access to {$ability}");
            }
        });

        it('handles edge cases with user admin status', function () {
            // Test with explicitly false admin status
            $explicitlyNonAdminUser = User::factory()->create(['is_admin' => false]);
            expect($this->policy->before($explicitlyNonAdminUser, 'viewAny'))->toBeNull();

            // Test with explicitly true admin status
            $explicitlyAdminUser = User::factory()->create(['is_admin' => true]);
            expect($this->policy->before($explicitlyAdminUser, 'viewAny'))->toBeTrue();
        });
    });
});
