<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory database for faster tests
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('database.connections.sqlite_queue.database', ':memory:');
        Config::set('database.connections.sqlite_telescope.database', ':memory:');
        Config::set('database.connections.sqlite_health.database', ':memory:');

        // Disable health checks for tests
        Config::set('health.result_stores', []);

        // Disable CSRF verification for tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }
}
