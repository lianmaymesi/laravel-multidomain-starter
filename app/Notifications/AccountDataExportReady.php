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
        $ttl = AccountDataExport::EXPORT_TTL_DAYS;

        return (new MailMessage)
            ->subject(__('Your :app data export is ready', ['app' => $appName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your data export has been generated and is ready to download.'))
            ->action(__('Download My Data'), $this->export->downloadUrl())
            ->line(__('This link expires on **:expires** (:ttl days). Download before then — it will not be regenerated automatically.', ['expires' => $expires, 'ttl' => $ttl]))
            ->line(__('The export contains your profile data and account history in CSV format.'))
            ->salutation(__('— The :app Team', ['app' => $appName]));
    }
}
