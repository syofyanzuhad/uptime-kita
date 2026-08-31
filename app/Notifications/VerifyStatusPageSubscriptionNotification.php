<?php

namespace App\Notifications;

use App\Models\StatusPageSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyStatusPageSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public StatusPageSubscriber $subscriber) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusPage = $this->subscriber->statusPage;
        $verifyUrl = url('/status-subscription/verify/'.$this->subscriber->verification_token);

        return (new MailMessage)
            ->subject("Confirm your subscription to {$statusPage->title}")
            ->greeting('Hello!')
            ->line("You requested to receive email notifications when the status of services on **{$statusPage->title}** changes.")
            ->action('Confirm Subscription', $verifyUrl)
            ->line('If you did not request this subscription, no further action is required.');
    }
}
