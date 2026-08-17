<?php

namespace App\Console\Commands;

use App\Models\Monitor;
use Illuminate\Console\Command;

class EnableDomainExpirationCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:enable-domain-expiration-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable domain expiration checking for all existing monitors';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $updated = Monitor::withoutGlobalScope('enabled')
            ->where('domain_expiration_check_enabled', false)
            ->update(['domain_expiration_check_enabled' => true]);

        if ($updated === 0) {
            $this->info('Domain expiration checking was already enabled for all monitors.');

            return;
        }

        $this->info("Enabled domain expiration checking for {$updated} monitor(s).");
    }
}
