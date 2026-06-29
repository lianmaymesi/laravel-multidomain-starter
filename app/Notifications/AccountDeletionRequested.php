<?php

namespace App\Notifications;

use App\Models\AccountDeletionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AccountDeletionRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName  = config('app.name');
        $date     = $this->request->scheduled_at->format('F j, Y');
        $days     = AccountDeletionRequest::GRACE_PERIOD_DAYS;

        return (new MailMessage)
            ->subject("Your {$appName} account is scheduled for deletion")
            ->greeting("Hello {$notifiable->name},")
            ->line("We received a request to permanently delete your {$appName} account.")
            ->line("**Your account will be deleted on {$date}** (in {$days} days).")
            ->line('During this period your account remains fully active. If this was a mistake, you can cancel the deletion from your Security settings.')
            ->action('Cancel Deletion', route('account.security'))
            ->line('If you did not request this, please contact support immediately.')
            ->salutation("— The {$appName} Team");
    }
}
