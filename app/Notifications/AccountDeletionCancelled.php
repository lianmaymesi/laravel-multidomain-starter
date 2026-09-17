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
            ->subject(__('Your :app account deletion has been cancelled', ['app' => $appName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your account deletion request has been successfully cancelled.'))
            ->line(__('Your account is fully active and no data has been removed.'))
            ->action(__('Go to Security Settings'), route('account.security'))
            ->line(__('If you did not cancel this, please contact support immediately.'))
            ->salutation(__('— The :app Team', ['app' => $appName]));
    }
}
