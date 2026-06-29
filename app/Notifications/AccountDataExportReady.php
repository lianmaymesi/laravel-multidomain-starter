<?php

namespace App\Notifications;

use App\Models\AccountDataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDataExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AccountDataExport $export) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');
        $expires = $this->export->expires_at->format('F j, Y');
        $ttl     = AccountDataExport::EXPORT_TTL_DAYS;

        return (new MailMessage)
            ->subject("Your {$appName} data export is ready")
            ->greeting("Hello {$notifiable->name},")
            ->line('Your data export has been generated and is ready to download.')
            ->action('Download My Data', $this->export->downloadUrl())
            ->line("This link expires on **{$expires}** ({$ttl} days). Download before then — it will not be regenerated automatically.")
            ->line('The export contains your profile data and account history in CSV format.')
            ->salutation("— The {$appName} Team");
    }
}
