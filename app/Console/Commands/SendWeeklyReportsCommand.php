<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyMonitorReportJob;
use App\Models\User;
use Illuminate\Console\Command;

class SendWeeklyReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-weekly 
                            {--user= : Target a specific user ID or email}
                            {--sync : Run job synchronously instead of queuing}
                            {--dry-run : List eligible recipients without dispatching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch weekly uptime report emails to eligible users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdOrEmail = $this->option('user');
        $sync = (bool) $this->option('sync');
        $dryRun = (bool) $this->option('dry-run');

        $query = User::query()
            ->where('weekly_report_enabled', true)
            ->whereHas('monitors', fn ($q) => $q->where('uptime_check_enabled', true));

        if ($userIdOrEmail) {
            $query->where(function ($q) use ($userIdOrEmail) {
                if (is_numeric($userIdOrEmail)) {
                    $q->where('id', (int) $userIdOrEmail);
                } else {
                    $q->where('email', $userIdOrEmail);
                }
            });
        }

        $totalEligible = $query->count();

        if ($totalEligible === 0) {
            $this->warn('No eligible users found for weekly reports.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Dry run: Found {$totalEligible} eligible user(s).");
            $users = $query->select(['id', 'name', 'email'])->get();
            $this->table(['ID', 'Name', 'Email'], $users->toArray());

            return self::SUCCESS;
        }

        $this->info("Dispatching weekly reports for {$totalEligible} user(s)...");

        $dispatched = 0;

        $query->chunk(100, function ($users) use ($sync, &$dispatched) {
            foreach ($users as $user) {
                if ($sync) {
                    SendWeeklyMonitorReportJob::dispatchSync($user);
                } else {
                    SendWeeklyMonitorReportJob::dispatch($user)
                        ->onQueue('default');
                }
                $dispatched++;
            }
        });

        $mode = $sync ? 'synchronously' : 'to default queue';
        $this->info("Successfully dispatched {$dispatched} weekly report(s) ({$mode}).");

        return self::SUCCESS;
    }
}
