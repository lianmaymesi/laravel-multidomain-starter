<?php

namespace Atrium\Core\Notifications;

use Atrium\Core\Support\Health\Report;
use Atrium\Core\Support\Health\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to HEALTH_NOTIFY_MAIL when the overall health status changes. */
class HealthStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Report $report, public ?Status $previous) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->report->status();
        $app = config('app.name');

        $mail = (new MailMessage)
            ->subject("[{$app}] Health: ".strtoupper($status->value))
            ->line('Overall status changed from **'.($this->previous->value ?? 'unknown')."** to **{$status->value}** at ".$this->report->checkedAt->toDayDateTimeString().'.');

        foreach ($this->report->checks as $check) {
            if ($check['result']->status !== Status::Ok) {
                $mail->line("• {$check['label']} — {$check['result']->status->value}: {$check['result']->message}");
            }
        }

        return $status === Status::Ok ? $mail->success() : $mail->error();
    }
}
