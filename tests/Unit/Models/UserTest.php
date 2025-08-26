<?php

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\SocialAccount;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Model', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $user = User::create([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'secret123',
            ]);

            expect($user->name)->toBe('Jane Doe');
            expect($user->email)->toBe('jane@example.com');
            expect($user->password)->not->toBe('secret123'); // Should be hashed
        });

        it('prevents mass assignment of non-fillable attributes', function () {
            // Test that mass assignment respects fillable rules
            expect(function () {
                User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => 'password',
                    'is_admin' => true, // This should not be mass assignable
                ]);
            })->toThrow(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        });
    });

    describe('hidden attributes', function () {
        it('hides password and remember_token in array conversion', function () {
            $userArray = $this->user->toArray();

            expect($userArray)->not->toHaveKey('password');
            expect($userArray)->not->toHaveKey('remember_token');
        });

        it('includes other attributes in array conversion', function () {
            $userArray = $this->user->toArray();

            expect($userArray)->toHaveKey('name');
            expect($userArray)->toHaveKey('email');
            expect($userArray)->toHaveKey('id');
        });
    });

    describe('casts', function () {
        it('casts email_verified_at to datetime', function () {
            $user = User::factory()->create([
                'email_verified_at' => '2024-01-01 12:00:00',
            ]);

            expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
        });

        it('casts password to hashed', function () {
            $plainPassword = 'plain-password';
            $user = User::factory()->create([
                'password' => $plainPassword,
            ]);

            expect($user->password)->not->toBe($plainPassword);
            expect(Hash::check($plainPassword, $user->password))->toBeTrue();
        });
    });

    describe('socialAccounts relationship', function () {
        it('has many social accounts', function () {
            $socialAccount1 = SocialAccount::factory()->create(['user_id' => $this->user->id]);
            $socialAccount2 = SocialAccount::factory()->create(['user_id' => $this->user->id]);

            expect($this->user->socialAccounts)->toHaveCount(2);
            expect($this->user->socialAccounts->pluck('id'))
                ->toContain($socialAccount1->id)
                ->toContain($socialAccount2->id);
        });
    });

    describe('monitors relationship', function () {
        it('belongs to many monitors through pivot table', function () {
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            $this->user->monitors()->attach($monitor1->id, ['is_active' => true, 'is_pinned' => false]);
            $this->user->monitors()->attach($monitor2->id, ['is_active' => false, 'is_pinned' => true]);

            expect($this->user->monitors)->toHaveCount(2);
            expect($this->user->monitors->pluck('id'))
                ->toContain($monitor1->id)
                ->toContain($monitor2->id);
        });

        it('includes pivot data in relationship', function () {
            $monitor = Monitor::factory()->create();
            $this->user->monitors()->attach($monitor->id, [
                'is_active' => true,
                'is_pinned' => false,
            ]);

            $attachedMonitor = $this->user->monitors()->first();

            expect($attachedMonitor->pivot->is_active)->toBe(1); // SQLite returns as integer
            expect($attachedMonitor->pivot->is_pinned)->toBe(0);
        });
    });

    describe('statusPages relationship', function () {
        it('has many status pages', function () {
            $statusPage1 = StatusPage::factory()->create(['user_id' => $this->user->id]);
            $statusPage2 = StatusPage::factory()->create(['user_id' => $this->user->id]);

            expect($this->user->statusPages)->toHaveCount(2);
            expect($this->user->statusPages->pluck('id'))
                ->toContain($statusPage1->id)
                ->toContain($statusPage2->id);
        });
    });

    describe('notificationChannels relationship', function () {
        it('has many notification channels', function () {
            $channel1 = NotificationChannel::factory()->create(['user_id' => $this->user->id]);
            $channel2 = NotificationChannel::factory()->create(['user_id' => $this->user->id]);

            expect($this->user->notificationChannels)->toHaveCount(2);
            expect($this->user->notificationChannels->pluck('id'))
                ->toContain($channel1->id)
                ->toContain($channel2->id);
        });
    });

    describe('active scope', function () {
        it('returns users with active monitor subscriptions', function () {
            $activeUser = User::factory()->create();
            $inactiveUser = User::factory()->create();
            $noMonitorUser = User::factory()->create();

            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            // Active user with active monitor
            $activeUser->monitors()->attach($monitor1->id, ['is_active' => true]);

            // Inactive user with inactive monitor
            $inactiveUser->monitors()->attach($monitor2->id, ['is_active' => false]);

            // No monitor user has no monitors attached

            $activeUsers = User::active()->get();

            expect($activeUsers->pluck('id'))->toContain($activeUser->id);
            expect($activeUsers->pluck('id'))->not->toContain($inactiveUser->id);
            expect($activeUsers->pluck('id'))->not->toContain($noMonitorUser->id);
        });

        it('includes users with at least one active monitor', function () {
            $mixedUser = User::factory()->create();
            $monitor1 = Monitor::factory()->create();
            $monitor2 = Monitor::factory()->create();

            // User with both active and inactive monitors
            $mixedUser->monitors()->attach($monitor1->id, ['is_active' => true]);
            $mixedUser->monitors()->attach($monitor2->id, ['is_active' => false]);

            $activeUsers = User::active()->get();

            expect($activeUsers->pluck('id'))->toContain($mixedUser->id);
        });
    });

    describe('authentication features', function () {
        it('extends laravel authenticatable', function () {
            expect($this->user)->toBeInstanceOf(\Illuminate\Foundation\Auth\User::class);
        });

        it('uses notifiable trait', function () {
            expect(method_exists($this->user, 'notify'))->toBeTrue();
            expect(method_exists($this->user, 'routeNotificationFor'))->toBeTrue();
        });

        it('uses has factory trait', function () {
            expect(method_exists($this->user, 'factory'))->toBeTrue();
            $factoryUser = User::factory()->create();
            expect($factoryUser)->toBeInstanceOf(User::class);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            expect($this->user->getTable())->toBe('users');
        });

        it('has incrementing primary key', function () {
            expect($this->user->getIncrementing())->toBeTrue();
            expect($this->user->getKeyType())->toBe('int');
        });

        it('handles timestamps', function () {
            expect($this->user->timestamps)->toBeTrue();
            expect($this->user->created_at)->not->toBeNull();
            expect($this->user->updated_at)->not->toBeNull();
        });
    });
});
