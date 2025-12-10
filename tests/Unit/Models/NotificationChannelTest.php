<?php

use App\Models\NotificationChannel;
use App\Models\User;

describe('NotificationChannel Model', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->notificationChannel = NotificationChannel::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'destination' => 'test@example.com',
            'is_enabled' => true,
            'metadata' => ['key' => 'value'],
        ]);
    });

    describe('fillable attributes', function () {
        it('allows mass assignment of fillable attributes', function () {
            $channel = NotificationChannel::create([
                'user_id' => $this->user->id,
                'type' => 'slack',
                'destination' => 'https://hooks.slack.com/webhook',
                'is_enabled' => false,
                'metadata' => ['webhook_url' => 'https://example.com/webhook'],
            ]);

            expect($channel->type)->toBe('slack');
            expect($channel->destination)->toBe('https://hooks.slack.com/webhook');
            expect($channel->is_enabled)->toBeFalse();
            expect($channel->metadata)->toBeArray();
            expect($channel->metadata['webhook_url'])->toBe('https://example.com/webhook');
        });
    });

    describe('casts', function () {
        it('casts is_enabled to boolean', function () {
            $channel = NotificationChannel::factory()->create([
                'user_id' => $this->user->id,
                'is_enabled' => 1,
            ]);

            expect($channel->is_enabled)->toBeTrue();
            expect(is_bool($channel->is_enabled))->toBeTrue();
        });

        it('casts metadata to array', function () {
            $metadata = ['webhookUrl' => 'https://example.com', 'token' => 'secret'];
            $channel = NotificationChannel::factory()->create([
                'metadata' => $metadata,
            ]);

            expect($channel->metadata)->toBeArray();
            expect($channel->metadata)->toBe($metadata);
        });

        it('handles null metadata', function () {
            $channel = NotificationChannel::factory()->create([
                'metadata' => null,
            ]);

            expect($channel->metadata)->toBeNull();
        });
    });

    describe('user relationship', function () {
        it('belongs to a user', function () {
            expect($this->notificationChannel->user)->toBeInstanceOf(User::class);
            expect($this->notificationChannel->user->id)->toBe($this->user->id);
        });
    });

    describe('notification types', function () {
        it('supports different notification types', function () {
            $types = ['email', 'slack', 'webhook', 'telegram'];

            foreach ($types as $index => $type) {
                $channel = NotificationChannel::factory()->create([
                    'user_id' => $this->user->id,
                    'type' => $type,
                    'destination' => "test-{$index}@example.com",
                ]);

                expect($channel->type)->toBe($type);
            }
        });
    });

    describe('enabled/disabled states', function () {
        it('can be enabled or disabled', function () {
            $enabledChannel = NotificationChannel::factory()->create([
                'is_enabled' => true,
            ]);

            $disabledChannel = NotificationChannel::factory()->create([
                'is_enabled' => false,
            ]);

            expect($enabledChannel->is_enabled)->toBeTrue();
            expect($disabledChannel->is_enabled)->toBeFalse();
        });
    });

    describe('metadata storage', function () {
        it('stores complex metadata structures', function () {
            $complexMetadata = [
                'settings' => [
                    'webhook_url' => 'https://example.com/webhook',
                    'timeout' => 30,
                    'retry_count' => 3,
                ],
                'auth' => [
                    'token' => 'secret-token',
                    'headers' => ['X-API-Key' => 'api-key'],
                ],
            ];

            $channel = NotificationChannel::factory()->create([
                'metadata' => $complexMetadata,
            ]);

            expect($channel->metadata)->toBe($complexMetadata);
            expect($channel->metadata['settings']['webhook_url'])->toBe('https://example.com/webhook');
            expect($channel->metadata['auth']['token'])->toBe('secret-token');
        });

        it('handles empty metadata', function () {
            $channel = NotificationChannel::factory()->create([
                'metadata' => [],
            ]);

            expect($channel->metadata)->toBe([]);
        });
    });

    describe('model attributes', function () {
        it('has correct table name', function () {
            expect($this->notificationChannel->getTable())->toBe('notification_channels');
        });

        it('uses factory trait', function () {
            expect(method_exists($this->notificationChannel, 'factory'))->toBeTrue();
            $factoryChannel = NotificationChannel::factory()->create(['user_id' => $this->user->id]);
            expect($factoryChannel)->toBeInstanceOf(NotificationChannel::class);
        });

        it('handles timestamps', function () {
            expect($this->notificationChannel->timestamps)->toBeTrue();
            expect($this->notificationChannel->created_at)->not->toBeNull();
            expect($this->notificationChannel->updated_at)->not->toBeNull();
        });
    });
});
