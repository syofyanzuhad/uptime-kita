<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use NotificationChannels\Telegram\TelegramMessage;

class WeeklyMonitorReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Pre-computed summaries per monitor.
     */
    public Collection $monitorSummaries;

    /**
     * Fleet-level aggregated metrics.
     *
     * @var array<string, mixed>
     */
    public array $fleetTotals = [];

    /**
     * Human-readable period string.
     */
    public string $period;

    /**
     * Create a new notification instance.
     *
     * Supports both:
     * - new WeeklyMonitorReport($summaries, $fleetTotals, $period)
     * - new WeeklyMonitorReport($monitors, $summaries, $fleetTotals, $period)
     *
     * @param  mixed  $monitorsOrSummaries  Collection|array
     * @param  mixed  $summaries  Collection|array|null
     * @param  array<string, mixed>  $fleetTotals
     */
    public function __construct(
        mixed $arg1,
        mixed $arg2 = null,
        mixed $arg3 = null,
        mixed $arg4 = null
    ) {
        if ($arg2 instanceof Collection || (is_array($arg2) && isset($arg2[0]))) {
            // (monitors, summaries, fleetTotals, period)
            $this->monitorSummaries = collect($arg2);
            $this->fleetTotals = is_array($arg3) ? $arg3 : [];
            $this->period = is_string($arg4) ? $arg4 : (is_string($arg3) ? $arg3 : '');
        } else {
            // (summaries, fleetTotals, period)
            $this->monitorSummaries = collect($arg1);
            $this->fleetTotals = is_array($arg2) ? $arg2 : [];
            $this->period = is_string($arg3) ? $arg3 : (is_string($arg2) ? $arg2 : '');
        }

        if (empty($this->period)) {
            $this->period = now()->startOfWeek()->subWeek()->format('M d').' – '.now()->startOfWeek()->subWeek()->endOfWeek()->format('M d, Y');
        }

        if (empty($this->fleetTotals)) {
            $this->fleetTotals = $this->calculateFleetTotals();
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = $notifiable->relationLoaded('notificationChannels')
            ? $notifiable->notificationChannels->where('is_enabled', true)
            : $notifiable->notificationChannels()->where('is_enabled', true)->get();

        $via = [];

        // Primary email channel
        if (! empty($notifiable->email)) {
            $via[] = 'mail';
        }

        foreach ($channels as $channel) {
            if ($channel->type === 'email' && ! in_array('mail', $via, true)) {
                $via[] = 'mail';
            } elseif ($channel->type === 'telegram') {
                if (! blank($channel->destination) && preg_match('/^-?\d+$/', (string) $channel->destination)) {
                    $via[] = 'telegram';
                }
            } elseif ($channel->type === 'slack') {
                $via[] = 'slack';
            }
        }

        return array_values(array_unique($via));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $userId = $notifiable->id ?? 0;
        $unsubscribeUrl = URL::signedRoute('settings.weekly-report.unsubscribe', ['user' => $userId]);

        return (new MailMessage)
            ->subject("Your Weekly Uptime Report — {$this->period}")
            ->view('emails.weekly-report', [
                'user' => $notifiable,
                'summaries' => $this->monitorSummaries,
                'fleetTotals' => $this->fleetTotals,
                'period' => $this->period,
                'unsubscribeUrl' => $unsubscribeUrl,
                'dashboardUrl' => url('/dashboard'),
            ]);
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): TelegramMessage
    {
        $totalMonitors = $this->fleetTotals['total_monitors'] ?? $this->monitorSummaries->count();
        $upMonitors = $this->fleetTotals['up_monitors'] ?? 0;
        $avgUptime = number_format($this->fleetTotals['average_uptime'] ?? 100, 2);
        $totalDowntime = $this->fleetTotals['total_downtime_mins'] ?? 0;

        $content = "📊 *Your Weekly Uptime Report*\n"
            ."_{$this->period}_\n\n"
            ."*Fleet Health:* {$upMonitors}/{$totalMonitors} Up\n"
            ."*Average Uptime:* {$avgUptime}%\n"
            ."*Total Downtime:* {$totalDowntime} mins\n\n";

        if (! empty($this->fleetTotals['best_performer'])) {
            $best = $this->fleetTotals['best_performer'];
            $bestUptime = number_format($best['uptime_7d'] ?? 100, 2);
            $content .= "🏆 *Best:* `{$best['display_name']}` ({$bestUptime}%, {$best['avg_response_ms']}ms)\n";
        }

        if (! empty($this->fleetTotals['worst_performer']) && (($this->fleetTotals['worst_performer']['uptime_7d'] ?? 100) < 100 || ($this->fleetTotals['worst_performer']['total_downtime_mins'] ?? 0) > 0)) {
            $worst = $this->fleetTotals['worst_performer'];
            $worstUptime = number_format($worst['uptime_7d'] ?? 100, 2);
            $content .= "⚠️ *Worst:* `{$worst['display_name']}` ({$worstUptime}%, {$worst['total_downtime_mins']}m down)\n";
        }

        $content .= "\n*Top Monitors:*\n";
        foreach ($this->monitorSummaries->take(5) as $summary) {
            $statusEmoji = ($summary['is_up_now'] ?? true) ? '🟢' : '🔴';
            $uptime = number_format($summary['uptime_7d'] ?? 100, 2);
            $content .= "{$statusEmoji} *{$summary['display_name']}*: {$uptime}% ({$summary['avg_response_ms']}ms)\n";
        }

        return TelegramMessage::create()
            ->content($content)
            ->options(['parse_mode' => 'Markdown'])
            ->button('View Dashboard', url('/dashboard'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'period' => $this->period,
            'fleet_totals' => $this->fleetTotals,
            'monitors_count' => $this->monitorSummaries->count(),
            'summaries' => $this->monitorSummaries->toArray(),
        ];
    }

    /**
     * Calculate fleet totals from monitor summaries.
     *
     * @return array<string, mixed>
     */
    protected function calculateFleetTotals(): array
    {
        $totalMonitors = $this->monitorSummaries->count();
        $upMonitors = $this->monitorSummaries->where('is_up_now', true)->count();
        $downMonitors = $totalMonitors - $upMonitors;
        $avgUptime = $totalMonitors > 0 ? round((float) $this->monitorSummaries->avg('uptime_7d'), 2) : 100.0;
        $totalDowntimeMins = (int) $this->monitorSummaries->sum('total_downtime_mins');

        $worstPerformer = $this->monitorSummaries->sortBy('uptime_7d')->first();
        $bestPerformer = $this->monitorSummaries->sortByDesc('uptime_7d')->sortBy('avg_response_ms')->first();

        return [
            'total_monitors' => $totalMonitors,
            'up_monitors' => $upMonitors,
            'down_monitors' => $downMonitors,
            'average_uptime' => $avgUptime,
            'total_downtime_mins' => $totalDowntimeMins,
            'worst_performer' => $worstPerformer,
            'best_performer' => $bestPerformer,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Weekly monitor report notification failed', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
