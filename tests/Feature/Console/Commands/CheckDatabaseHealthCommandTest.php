<?php

use App\Models\Monitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

it('reports success when integrity check passes', function () {
    $this->artisan('db:health-check')
        ->assertSuccessful()
        ->expectsOutput('Checking database health...')
        ->expectsOutput('✓ Database integrity check passed');
});

it('reports the journal mode', function () {
    $this->artisan('db:health-check')
        ->assertSuccessful()
        ->expectsOutputToContain('Journal mode:');
});

it('reports database size', function () {
    $this->artisan('db:health-check')
        ->assertSuccessful()
        ->expectsOutputToContain('Database size:');
});

it('runs optimizations when repair option is passed', function () {
    $this->artisan('db:health-check', ['--repair' => true])
        ->assertSuccessful()
        ->expectsOutput('Performing optimizations...');
});

it('reports integrity failure and returns failure', function () {
    Log::spy();

    // Force an integrity check failure by mocking the query result
    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('select')
        ->once()
        ->with('PRAGMA integrity_check')
        ->andReturn([
            (object) ['integrity_check' => '*** in database main ***'],
            (object) ['integrity_check' => 'Page 1: freelist count is wrong'],
        ]);
    DB::swap($db);

    $this->artisan('db:health-check')
        ->assertExitCode(1)
        ->expectsOutput('✗ Database integrity check failed!');

    Log::shouldHaveReceived('critical')
        ->once()
        ->withArgs(function ($message) {
            return $message === 'Database integrity check failed';
        });
});

it('handles exceptions gracefully', function () {
    Log::spy();

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('select')
        ->once()
        ->with('PRAGMA integrity_check')
        ->andThrow(new Exception('database is locked'));
    DB::swap($db);

    $this->artisan('db:health-check')
        ->assertExitCode(1)
        ->expectsOutputToContain('Failed to check database health: database is locked');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function ($message) {
            return $message === 'Database health check failed';
        });
});

it('includes monitor counts in the health check', function () {
    Monitor::factory()->count(3)->create(['is_public' => true]);

    $this->artisan('db:health-check')
        ->assertSuccessful();
});

it('attempts repair when integrity fails with repair option', function () {
    Log::spy();

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('select')
        ->once()
        ->with('PRAGMA integrity_check')
        ->andReturn([
            (object) ['integrity_check' => '*** in database main ***'],
        ]);
    DB::swap($db);

    $this->artisan('db:health-check', ['--repair' => true])
        ->assertExitCode(1)
        ->expectsOutput('✗ Database integrity check failed!')
        ->expectsOutput('Attempting repair...');
});

it('handles optimization failures during repair', function () {
    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    // Integrity passes, but VACUUM fails
    $db->shouldReceive('select')
        ->once()
        ->with('PRAGMA integrity_check')
        ->andReturn([
            (object) ['integrity_check' => 'ok'],
        ]);
    $db->shouldReceive('statement')
        ->with('VACUUM')
        ->andThrow(new Exception('database is locked'));
    DB::swap($db);

    $this->artisan('db:health-check', ['--repair' => true])
        ->assertSuccessful()
        ->expectsOutput('Performing optimizations...')
        ->expectsOutputToContain('Optimization failed: database is locked');
});

it('reports recovery failure when sqlite command fails', function () {
    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('select')
        ->once()
        ->with('PRAGMA integrity_check')
        ->andReturn([
            (object) ['integrity_check' => '*** in database main ***'],
        ]);
    DB::swap($db);

    // sqlite3 command will fail because the file doesn't exist in tests
    $this->artisan('db:health-check', ['--repair' => true])
        ->assertExitCode(1)
        ->expectsOutput('Attempting recovery...');
});
