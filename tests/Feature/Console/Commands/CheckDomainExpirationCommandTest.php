<?php

use App\Jobs\CheckDomainExpirationJob;
use App\Models\Monitor;
use Illuminate\Support\Facades\Queue;

it('dispatches check jobs for monitors with domain expiration enabled', function () {
    Queue::fake();

    $enabled = Monitor::factory()->create([
        'domain_expiration_check_enabled' => true,
    ]);

    Monitor::factory()->create([
        'domain_expiration_check_enabled' => false,
    ]);

    $this->artisan('monitor:check-domain-expiration')
        ->assertSuccessful();

    Queue::assertPushed(CheckDomainExpirationJob::class, 1);
    Queue::assertPushed(CheckDomainExpirationJob::class, fn ($job) => $job->monitor->is($enabled));
});

it('does not dispatch any jobs when no monitors have domain expiration enabled', function () {
    Queue::fake();

    Monitor::factory()->create([
        'domain_expiration_check_enabled' => false,
    ]);

    $this->artisan('monitor:check-domain-expiration')
        ->assertSuccessful()
        ->expectsOutput('No monitors with domain expiration checking enabled.');

    Queue::assertNotPushed(CheckDomainExpirationJob::class);
});
