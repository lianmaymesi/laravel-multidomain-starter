<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject("Your {$appName} account deletion has been cancelled")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your account deletion request has been successfully cancelled.")
            ->line('Your account is fully active and no data has been removed.')
            ->action('Go to Security Settings', route('account.security'))
            ->line('If you did not cancel this, please contact support immediately.')
            ->salutation("— The {$appName} Team");
    }
}
