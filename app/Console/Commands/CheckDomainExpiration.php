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
        $count = 0;

        Monitor::withoutGlobalScope('enabled')
            ->where('domain_expiration_check_enabled', true)
            ->lazy()
            ->each(function (Monitor $monitor) use (&$count) {
                CheckDomainExpirationJob::dispatch($monitor);
                $count++;
            });

        if ($count === 0) {
            $this->info('No monitors with domain expiration checking enabled.');

            return;
        }

        $this->info("Dispatched domain expiration checks for {$count} monitors.");
    }
}
