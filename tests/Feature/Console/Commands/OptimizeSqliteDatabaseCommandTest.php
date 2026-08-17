<?php

use Illuminate\Support\Facades\Log;

it('completes optimization successfully', function () {
    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $this->artisan('sqlite:optimize')
        ->assertSuccessful()
        ->expectsOutputToContain('Starting optimization');
});

it('applies pragma tuning and runs analyze and vacuum', function () {
    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $this->artisan('sqlite:optimize')
        ->assertSuccessful()
        ->expectsOutput('⚙️ Applying SQLite PRAGMA tuning...')
        ->expectsOutput('🔍 Running ANALYZE...')
        ->expectsOutput('💾 Running VACUUM (this may take a while)...');
});

it('logs success message', function () {
    Log::spy();

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('statement')->andReturn(true);
    DB::swap($db);

    $this->artisan('sqlite:optimize')->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(function ($message) {
            return str_contains($message, 'SQLite optimization completed successfully');
        });
});

it('handles failures gracefully', function () {
    Log::spy();

    $db = Mockery::mock(DB::getFacadeRoot())->makePartial();
    $db->shouldReceive('statement')
        ->once()
        ->with('PRAGMA journal_mode = WAL;')
        ->andThrow(new Exception('database is locked'));
    DB::swap($db);

    $this->artisan('sqlite:optimize')
        ->assertExitCode(1)
        ->expectsOutputToContain('❌ Optimization failed: database is locked');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function ($message) {
            return $message === 'SQLite optimization failed';
        });
});
