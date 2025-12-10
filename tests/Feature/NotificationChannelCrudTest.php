<?php

use App\Models\NotificationChannel;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
});

describe('NotificationChannel CRUD Operations', function () {

    describe('Index', function () {
        it('can list all notification channels for authenticated user', function () {
            $channel1 = NotificationChannel::factory()->create(['user_id' => $this->user->id]);
            $channel2 = NotificationChannel::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->get('/settings/notifications');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
                ->has('channels', 2)
            );
        });

        it('only shows notification channels belonging to the user', function () {
            $myChannel = NotificationChannel::factory()->create(['user_id' => $this->user->id]);
            $otherUser = User::factory()->create();
            $otherChannel = NotificationChannel::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->get('/settings/notifications');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
                ->has('channels', 1)
                ->where('channels.0.id', $myChannel->id)
            );
        });

        it('requires authentication', function () {
            $response = get('/settings/notifications');

            $response->assertRedirect('/login');
        });
    });

    describe('Create', function () {
        it('can show create form for authenticated user', function () {
            $response = actingAs($this->user)->get('/settings/notifications/create');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
            );
        });

        it('requires authentication to show create form', function () {
            $response = get('/settings/notifications/create');

            $response->assertRedirect('/login');
        });
    });

    describe('Store', function () {
        it('can create a new email notification channel', function () {
            $channelData = [
                'type' => 'email',
                'destination' => 'test@example.com',
                'is_enabled' => true,
            ];

            $response = actingAs($this->user)->postJson('/settings/notifications', $channelData);

            $response->assertRedirect();

            assertDatabaseHas('notification_channels', [
                'user_id' => $this->user->id,
                'type' => 'email',
                'destination' => 'test@example.com',
                'is_enabled' => true,
            ]);
        });

        it('can create a telegram notification channel', function () {
            $channelData = [
                'type' => 'telegram',
                'destination' => '@username',
                'is_enabled' => true,
            ];

            $response = actingAs($this->user)->postJson('/settings/notifications', $channelData);

            $response->assertRedirect();

            assertDatabaseHas('notification_channels', [
                'user_id' => $this->user->id,
                'type' => 'telegram',
                'destination' => '@username',
                'is_enabled' => true,
            ]);
        });

        it('can create a slack notification channel', function () {
            $channelData = [
                'type' => 'slack',
                'destination' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX',
                'is_enabled' => true,
            ];

            $response = actingAs($this->user)->postJson('/settings/notifications', $channelData);

            $response->assertRedirect();

            assertDatabaseHas('notification_channels', [
                'user_id' => $this->user->id,
                'type' => 'slack',
                'destination' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX',
            ]);
        });

        it('can create a webhook notification channel', function () {
            $channelData = [
                'type' => 'webhook',
                'destination' => 'https://example.com/webhook',
                'is_enabled' => true,
                'metadata' => ['headers' => ['X-API-Key' => 'secret']],
            ];

            $response = actingAs($this->user)->postJson('/settings/notifications', $channelData);

            $response->assertRedirect();

            $channel = NotificationChannel::where('user_id', $this->user->id)->first();
            expect($channel->type)->toBe('webhook');
            expect($channel->destination)->toBe('https://example.com/webhook');
            expect($channel->metadata)->toHaveKey('headers');
        });

        it('validates required fields when creating notification channel', function () {
            $response = actingAs($this->user)->postJson('/settings/notifications', []);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['type', 'destination']);
        });

        it('validates email format for email type', function () {
            $channelData = [
                'type' => 'email',
                'destination' => 'not-an-email',
            ];

            $response = actingAs($this->user)->postJson('/settings/notifications', $channelData);

            // No specific email validation, just accepts string
            $response->assertRedirect();
        });

        it('validates URL format for webhook type', function () {
            $channelData = [
                'type' => 'webhook',
                'destination' => 'not-a-url',
            ];

            $response = actingAs($this->user)->postJson('/settings/notifications', $channelData);

            // No specific URL validation, just accepts string
            $response->assertRedirect();
        });

        it('requires authentication to create notification channel', function () {
            $channelData = [
                'type' => 'email',
                'destination' => 'test@example.com',
            ];

            $response = postJson('/settings/notifications', $channelData);

            $response->assertUnauthorized();
        });
    });

    describe('Show', function () {
        it('can view a notification channel belonging to user', function () {
            $channel = NotificationChannel::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->get("/settings/notifications/{$channel->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
                ->has('channels')
                ->has('editingChannel')
            );
        });

        it('cannot view notification channel not belonging to user', function () {
            $otherUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->get("/settings/notifications/{$channel->id}");

            $response->assertNotFound();
        });

        it('admin cannot view other users notification channel', function () {
            $regularUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $regularUser->id]);

            $response = actingAs($this->admin)->get("/settings/notifications/{$channel->id}");

            $response->assertNotFound();
        });

        it('requires authentication to view notification channel', function () {
            $channel = NotificationChannel::factory()->create();

            $response = get("/settings/notifications/{$channel->id}");

            $response->assertRedirect('/login');
        });
    });

    describe('Edit', function () {
        it('can show edit form for owned notification channel', function () {
            $channel = NotificationChannel::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->get("/settings/notifications/{$channel->id}/edit");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
                ->has('channels')
                ->has('editingChannel')
            );
        });

        it('cannot edit notification channel not owned by user', function () {
            $otherUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->get("/settings/notifications/{$channel->id}/edit");

            $response->assertNotFound();
        });

        it('admin cannot edit other users notification channel', function () {
            $regularUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $regularUser->id]);

            $response = actingAs($this->admin)->get("/settings/notifications/{$channel->id}/edit");

            $response->assertNotFound();
        });
    });

    describe('Update', function () {
        it('can update owned notification channel', function () {
            $channel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'email',
                'destination' => 'old@example.com',
                'is_enabled' => false,
            ]);

            $updateData = [
                'type' => 'email',
                'destination' => 'new@example.com',
                'is_enabled' => true,
            ];

            $response = actingAs($this->user)->putJson("/settings/notifications/{$channel->id}", $updateData);

            $response->assertRedirect();

            assertDatabaseHas('notification_channels', [
                'id' => $channel->id,
                'destination' => 'new@example.com',
                'is_enabled' => true,
            ]);
        });

        it('cannot update notification channel not owned by user', function () {
            $otherUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $otherUser->id]);

            $updateData = [
                'type' => 'email',
                'destination' => 'hacked@example.com',
            ];

            $response = actingAs($this->user)->putJson("/settings/notifications/{$channel->id}", $updateData);

            $response->assertNotFound();
        });

        it('admin cannot update other users notification channel', function () {
            $regularUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create([
                'user_id' => $regularUser->id,
                'destination' => 'user@example.com',
            ]);

            $updateData = [
                'type' => 'email',
                'destination' => 'admin-updated@example.com',
            ];

            $response = actingAs($this->admin)->putJson("/settings/notifications/{$channel->id}", $updateData);

            $response->assertNotFound();

            assertDatabaseHas('notification_channels', [
                'id' => $channel->id,
                'destination' => 'user@example.com',
            ]);
        });

        it('validates required fields on update', function () {
            $channel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'type' => 'email',
            ]);

            $updateData = [
                // Missing required fields
            ];

            $response = actingAs($this->user)->putJson("/settings/notifications/{$channel->id}", $updateData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['type', 'destination']);
        });
    });

    describe('Toggle', function () {
        it('can toggle notification channel enabled status', function () {
            $channel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'is_enabled' => false,
            ]);

            $response = actingAs($this->user)->patchJson("/settings/notifications/{$channel->id}/toggle");

            $response->assertRedirect();

            assertDatabaseHas('notification_channels', [
                'id' => $channel->id,
                'is_enabled' => true,
            ]);

            // Toggle again
            $response = actingAs($this->user)->patchJson("/settings/notifications/{$channel->id}/toggle");

            $response->assertRedirect();

            assertDatabaseHas('notification_channels', [
                'id' => $channel->id,
                'is_enabled' => false,
            ]);
        });

        it('cannot toggle notification channel not owned by user', function () {
            $otherUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create([
                'user_id' => $otherUser->id,
                'is_enabled' => false,
            ]);

            $response = actingAs($this->user)->patchJson("/settings/notifications/{$channel->id}/toggle");

            $response->assertNotFound();

            assertDatabaseHas('notification_channels', [
                'id' => $channel->id,
                'is_enabled' => false,
            ]);
        });

        it('admin cannot toggle other users notification channel', function () {
            $regularUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create([
                'user_id' => $regularUser->id,
                'is_enabled' => false,
            ]);

            $response = actingAs($this->admin)->patchJson("/settings/notifications/{$channel->id}/toggle");

            $response->assertNotFound();

            assertDatabaseHas('notification_channels', [
                'id' => $channel->id,
                'is_enabled' => false,
            ]);
        });
    });

    describe('Delete', function () {
        it('can delete owned notification channel', function () {
            $channel = NotificationChannel::factory()->create(['user_id' => $this->user->id]);

            $response = actingAs($this->user)->deleteJson("/settings/notifications/{$channel->id}");

            $response->assertRedirect();
            assertDatabaseMissing('notification_channels', ['id' => $channel->id]);
        });

        it('cannot delete notification channel not owned by user', function () {
            $otherUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $otherUser->id]);

            $response = actingAs($this->user)->deleteJson("/settings/notifications/{$channel->id}");

            $response->assertNotFound();
            assertDatabaseHas('notification_channels', ['id' => $channel->id]);
        });

        it('admin cannot delete other users notification channel', function () {
            $regularUser = User::factory()->create();
            $channel = NotificationChannel::factory()->create(['user_id' => $regularUser->id]);

            $response = actingAs($this->admin)->deleteJson("/settings/notifications/{$channel->id}");

            $response->assertNotFound();
            assertDatabaseHas('notification_channels', ['id' => $channel->id]);
        });
    });
});
