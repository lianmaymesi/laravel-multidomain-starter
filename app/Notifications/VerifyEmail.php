<?php

namespace App\Notifications;

use App\Enums\OtpType;
use App\Services\Auth\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $code;

    /**
     * @param  string|null  $code  Pass an existing OTP, or null to auto-generate inside toMail().
     */
    public function __construct(?string $code = null)
    {
        $this->code = $code ?? '';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Auto-generate OTP if not provided (e.g. triggered from Registered event)
        if ($this->code === '') {
            $otp = app(OtpService::class)
                ->generate(
                    $notifiable,
                    OtpType::EMAIL_VERIFICATION
                );
            $this->code = $otp->code;
        }

        $appName = config('app.name');
        $expires = config('multidomain.otp.expires_minutes', 10);

        return (new MailMessage)
            ->subject("Your {$appName} email verification code")
            ->greeting("Hello {$notifiable->name},")
            ->line('Use the code below to verify your email address.')
            ->line('')
            ->line("**{$this->code}**")
            ->line('')
            ->line("This code expires in {$expires} minutes.")
            ->line('If you did not create an account, you can safely ignore this email.')
            ->salutation("— The {$appName} Team");
    }
}
