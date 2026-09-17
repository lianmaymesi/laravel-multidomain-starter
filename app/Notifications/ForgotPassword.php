<?php

namespace App\Notifications;

use App\Enums\OtpType;
use App\Services\Auth\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPassword extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(?string $code = null)
    {
        $this->code = $code ?? '';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->code === '') {
            $otp = app(OtpService::class)
                ->generate(
                    $notifiable,
                    OtpType::EMAIL_FORGOT_PASSWORD
                );
            $this->code = $otp->code;
        }

        $appName = config('app.name');
        $expires = config('multidomain.otp.expires_minutes', 10);

        return (new MailMessage)
            ->subject(__('Reset your password for :app', ['app' => $appName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('We received a request to reset your password.'))
            ->line(__('Use the code below to proceed with resetting your password:'))
            ->line('')
            ->line("**{$this->code}**")
            ->line('')
            ->line(__('This code will expire in :expires minutes.', ['expires' => $expires]))
            ->line(__('If you did not request a password reset, you can safely ignore this email.'))
            ->salutation(__('— The :app Team', ['app' => $appName]));
    }
}
