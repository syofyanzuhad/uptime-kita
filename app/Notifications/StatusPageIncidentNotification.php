<?php

namespace App\Notifications;

use App\Models\Monitor;
use App\Models\StatusPageSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusPageIncidentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public StatusPageSubscriber $subscriber,
        public Monitor $monitor,
        public string $status, // 'down' or 'up'
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusPage = $this->subscriber->statusPage;
        $statusPageUrl = $statusPage->getUrl();
        $unsubscribeUrl = url('/status-subscription/unsubscribe/'.$this->subscriber->unsubscribe_token);
        $serviceName = $this->monitor->display_name ?: $this->monitor->url;

        $isDown = strtolower($this->status) === 'down';
        $subjectStatus = $isDown ? 'Incident Reported' : 'Service Resolved';
        $subject = "[{$statusPage->title}] {$subjectStatus}: {$serviceName}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Status Update');

        if ($isDown) {
            $message->line("An incident has been detected on **{$serviceName}**.")
                ->line('Status: **DOWN**');
            if ($this->reason) {
                $message->line("Reason: {$this->reason}");
            }
        } else {
            $message->line("**{$serviceName}** has recovered and is operating normally.")
                ->line('Status: **OPERATIONAL**');
        }

        return $message
            ->action('View Status Page', $statusPageUrl)
            ->line('---')
            ->line("[Unsubscribe from updates]({$unsubscribeUrl})");
    }
}
