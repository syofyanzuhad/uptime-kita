<?php

namespace App\Console\Commands;

use App\Jobs\CheckDomainExpirationJob;
use App\Models\Monitor;
use Illuminate\Console\Command;

class CheckDomainExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:check-domain-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check domain expiration dates for monitors and send reminders';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $monitors = Monitor::withoutGlobalScope('enabled')
            ->where('domain_expiration_check_enabled', true)
            ->get();

        if ($monitors->isEmpty()) {
            $this->info('No monitors with domain expiration checking enabled.');

            return;
        }

        foreach ($monitors as $monitor) {
            CheckDomainExpirationJob::dispatch($monitor);
        }

        $this->info("Dispatched domain expiration checks for {$monitors->count()} monitors.");
    }
}
