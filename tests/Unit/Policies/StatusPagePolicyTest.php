<?php

use App\Models\StatusPage;
use App\Models\User;
use App\Policies\StatusPagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StatusPagePolicy', function () {
    beforeEach(function () {
        $this->policy = new StatusPagePolicy;
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->statusPage = StatusPage::factory()->create(['user_id' => $this->user->id]);
    });

    describe('viewAny', function () {
        it('allows any user to view any status pages', function () {
            expect($this->policy->viewAny($this->user))->toBeTrue();
            expect($this->policy->viewAny($this->otherUser))->toBeTrue();
        });
    });

    describe('view', function () {
        it('allows owner to view their status page', function () {
            expect($this->policy->view($this->user, $this->statusPage))->toBeTrue();
        });

        it('denies non-owner from viewing status page', function () {
            expect($this->policy->view($this->otherUser, $this->statusPage))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows any user to create status pages', function () {
            expect($this->policy->create($this->user))->toBeTrue();
            expect($this->policy->create($this->otherUser))->toBeTrue();
        });
    });

    describe('update', function () {
        it('allows owner to update their status page', function () {
            expect($this->policy->update($this->user, $this->statusPage))->toBeTrue();
        });

        it('denies non-owner from updating status page', function () {
            expect($this->policy->update($this->otherUser, $this->statusPage))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows owner to delete their status page', function () {
            expect($this->policy->delete($this->user, $this->statusPage))->toBeTrue();
        });

        it('denies non-owner from deleting status page', function () {
            expect($this->policy->delete($this->otherUser, $this->statusPage))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows owner to restore their status page', function () {
            expect($this->policy->restore($this->user, $this->statusPage))->toBeTrue();
        });

        it('denies non-owner from restoring status page', function () {
            expect($this->policy->restore($this->otherUser, $this->statusPage))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows owner to force delete their status page', function () {
            expect($this->policy->forceDelete($this->user, $this->statusPage))->toBeTrue();
        });

        it('denies non-owner from force deleting status page', function () {
            expect($this->policy->forceDelete($this->otherUser, $this->statusPage))->toBeFalse();
        });
    });

    describe('ownership validation', function () {
        it('correctly validates ownership across different users', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $statusPage1 = StatusPage::factory()->create(['user_id' => $user1->id]);
            $statusPage2 = StatusPage::factory()->create(['user_id' => $user2->id]);

            // User 1 should only access their own status page
            expect($this->policy->view($user1, $statusPage1))->toBeTrue();
            expect($this->policy->view($user1, $statusPage2))->toBeFalse();
            expect($this->policy->update($user1, $statusPage1))->toBeTrue();
            expect($this->policy->update($user1, $statusPage2))->toBeFalse();
            expect($this->policy->delete($user1, $statusPage1))->toBeTrue();
            expect($this->policy->delete($user1, $statusPage2))->toBeFalse();

            // User 2 should only access their own status page
            expect($this->policy->view($user2, $statusPage2))->toBeTrue();
            expect($this->policy->view($user2, $statusPage1))->toBeFalse();
            expect($this->policy->update($user2, $statusPage2))->toBeTrue();
            expect($this->policy->update($user2, $statusPage1))->toBeFalse();
            expect($this->policy->delete($user2, $statusPage2))->toBeTrue();
            expect($this->policy->delete($user2, $statusPage1))->toBeFalse();
        });
    });
});
