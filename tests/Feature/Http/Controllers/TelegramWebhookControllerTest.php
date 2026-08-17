<?php

use NotificationChannels\Telegram\Telegram;

// Constants to avoid duplication
const WEBHOOK_ENDPOINT = '/webhook/telegram';
const START_COMMAND = '/start';
const TEST_CHAT_ID = 123456789;
const TEST_USER_NAME = 'John';

beforeEach(function () {
    // Set a dummy telegram token for tests
    config(['services.telegram-bot-api.token' => 'test-token']);
    // Webhook verification is off by default unless a secret token is configured
    config(['services.telegram-bot-api.secret_token' => null]);
});

describe('TelegramWebhookController - secret token verification', function () {
    it('rejects requests with a missing secret token', function () {
        config(['services.telegram-bot-api.secret_token' => 'webhook-secret']);

        $response = $this->postJson(WEBHOOK_ENDPOINT, []);

        $response->assertForbidden();
    });

    it('rejects requests with an invalid secret token', function () {
        config(['services.telegram-bot-api.secret_token' => 'webhook-secret']);

        $response = $this->postJson(WEBHOOK_ENDPOINT, [], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ]);

        $response->assertForbidden();
    });

    it('accepts requests with the correct secret token', function () {
        config(['services.telegram-bot-api.secret_token' => 'webhook-secret']);

        $response = $this->postJson(WEBHOOK_ENDPOINT, [], [
            'X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('allows requests when no secret token is configured', function () {
        $response = $this->postJson(WEBHOOK_ENDPOINT, []);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });
});

describe('TelegramWebhookController - basic webhook responses', function () {
    it('responds with ok status for all requests', function () {
        $response = $this->postJson(WEBHOOK_ENDPOINT, []);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles empty webhook request', function () {
        $response = $this->postJson(WEBHOOK_ENDPOINT, []);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles malformed JSON request gracefully', function () {
        // The controller always returns 200 OK even for malformed data
        // as it handles it gracefully
        $response = $this->call('POST', WEBHOOK_ENDPOINT, [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');

        // Controller returns OK for all requests
        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });
});

describe('TelegramWebhookController - message filtering', function () {
    it('ignores non-start command messages', function () {
        $webhookData = [
            'message' => [
                'text' => 'Hello there!',
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles /START command in uppercase (case-sensitive)', function () {
        $webhookData = [
            'message' => [
                'text' => '/START',
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('ignores /start command with additional parameters', function () {
        $webhookData = [
            'message' => [
                'text' => START_COMMAND.' param1 param2',
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });
});

describe('TelegramWebhookController - message type handling', function () {
    it('handles updates without message text field', function () {
        $webhookData = [
            'message' => [
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
                // No 'text' field
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles updates with photo message instead of text', function () {
        $webhookData = [
            'message' => [
                'photo' => [
                    ['file_id' => 'photo123'],
                ],
                'caption' => START_COMMAND,
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles updates without message field', function () {
        $webhookData = [
            'update_id' => 123,
            'edited_message' => [
                'text' => START_COMMAND,
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles callback query updates', function () {
        $webhookData = [
            'callback_query' => [
                'id' => 'callback123',
                'data' => 'some_action',
                'from' => [
                    'id' => TEST_CHAT_ID,
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles inline query updates', function () {
        $webhookData = [
            'inline_query' => [
                'id' => 'inline123',
                'query' => 'search text',
                'from' => [
                    'id' => TEST_CHAT_ID,
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('handles channel post updates', function () {
        $webhookData = [
            'channel_post' => [
                'text' => START_COMMAND,
                'chat' => [
                    'id' => -1001234567890,
                    'type' => 'channel',
                    'title' => 'Test Channel',
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });
});

describe('TelegramWebhookController - /start command', function () {
    it('sends a telegram message for /start command', function () {
        // TelegramMessage resolves Telegram from the container; mock it to
        // avoid real HTTP calls.
        $telegram = Mockery::mock(Telegram::class);
        $telegram->shouldReceive('sendMessage')->once();
        app()->instance(Telegram::class, $telegram);

        $webhookData = [
            'message' => [
                'text' => '/start',
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    });

    it('does not send a telegram message for non-start commands', function () {
        $telegram = Mockery::mock(Telegram::class);
        $telegram->shouldNotReceive('sendMessage');
        app()->instance(Telegram::class, $telegram);

        $webhookData = [
            'message' => [
                'text' => 'Hello',
                'chat' => [
                    'id' => TEST_CHAT_ID,
                ],
                'from' => [
                    'first_name' => TEST_USER_NAME,
                ],
            ],
        ];

        $response = $this->postJson(WEBHOOK_ENDPOINT, $webhookData);

        $response->assertOk();
    });
});
