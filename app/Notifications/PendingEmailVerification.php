<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingEmailVerification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $pendingEmail,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url     = route('auth.verify-email-change', ['token' => $this->token]);
        $appName = config('app.name');

        return (new MailMessage)
            ->subject("Verify your new email address – {$appName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You requested to change your email address to **{$this->pendingEmail}**.")
            ->line('Click the button below to confirm. Your current email stays active until you verify the new one.')
            ->action('Verify new email', $url)
            ->line('This link expires in 48 hours.')
            ->line('If you did not request this change, ignore this email — your current email remains unchanged.')
            ->salutation("— The {$appName} Team");
    }
}
