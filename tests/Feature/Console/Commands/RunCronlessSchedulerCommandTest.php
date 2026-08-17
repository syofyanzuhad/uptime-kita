<?php

it('exits successfully when memory limit is reached', function () {
    $this->artisan('schedule:run-cronless-safe', [
        '--max-memory' => 0, // Any usage exceeds this immediately
        '--frequency' => 1,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Starting cronless scheduler')
        ->expectsOutputToContain('Memory limit reached');
});

it('exits successfully when max runtime is reached', function () {
    $this->artisan('schedule:run-cronless-safe', [
        '--max-runtime' => 0,
        '--frequency' => 1,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Max runtime reached, exiting for restart...');
});
