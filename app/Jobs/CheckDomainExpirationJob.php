<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Notifications\DomainExpiringNotification;
use App\Services\DomainExpirationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class CheckDomainExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Reminder thresholds (days before expiration) that each trigger one notification.
     */
    public const THRESHOLDS = [30, 14, 7, 3, 1];

    /**
     * Start sending a daily reminder once this many days (or fewer) remain.
     */
    public const DAILY_REMINDER_WINDOW_DAYS = 7;

    public function __construct(public Monitor $monitor) {}

    /**
     * Execute the job.
     */
    public function handle(DomainExpirationService $service): void
    {
        $host = $this->monitor->host;

        if (! $host) {
            return;
        }

        $previousDate = $this->monitor->domain_expiration_date;
        $expirationDate = $service->lookupExpirationDate($host);

        $this->monitor->update([
            'domain_expiration_date' => $expirationDate,
            'domain_expiration_lookup_error' => $expirationDate ? null : 'Tidak dapat mengambil tanggal kedaluwarsa domain',
        ]);

        if (! $expirationDate) {
            return;
        }

        // If the domain was renewed (expiration moved later), previously sent reminders no longer apply
        if ($previousDate && $expirationDate->gt($previousDate->copy()->addDay())) {
            $this->monitor->clearDomainExpirationReminders();
        }

        $this->sendDueReminders($expirationDate);
    }

    /**
     * Send the reminders that are due today (at most one per day).
     */
    protected function sendDueReminders(Carbon $expirationDate): void
    {
        $daysLeft = $this->daysLeft($expirationDate);
        $reminderKeys = [];

        foreach (self::THRESHOLDS as $threshold) {
            if ($daysLeft === $threshold) {
                $reminderKeys[] = "threshold_{$threshold}";
            }
        }

        // Daily reminder once the domain enters the final week (also covers expired domains)
        if ($daysLeft <= self::DAILY_REMINDER_WINDOW_DAYS) {
            $reminderKeys[] = 'daily_'.now()->toDateString();
        }

        foreach ($reminderKeys as $key) {
            if ($this->monitor->hasDomainExpirationReminderSent($key)) {
                continue;
            }

            $this->notifyUsers($daysLeft);
            $this->monitor->markDomainExpirationReminderSent($key);

            // Only send one notification per day, even when a threshold and the
            // daily reminder would both fire (e.g. exactly 7 days before expiry).
            break;
        }
    }

    /**
     * Number of whole days remaining until the domain expires (negative once expired).
     */
    protected function daysLeft(Carbon $expirationDate): int
    {
        return (int) now()->startOfDay()->diffInDays($expirationDate->copy()->startOfDay(), false);
    }

    /**
     * Notify all active subscribers of the monitor.
     */
    protected function notifyUsers(int $daysLeft): void
    {
        $users = $this->monitor->users()
            ->where('user_monitor.is_active', true)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DomainExpiringNotification($this->monitor, $daysLeft));
    }
}
