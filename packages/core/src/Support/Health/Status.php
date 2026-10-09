<?php

namespace Atrium\Core\Support\Health;

enum Status: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Failed = 'failed';

    public function severity(): int
    {
        return match ($this) {
            self::Ok => 0,
            self::Warning => 1,
            self::Failed => 2,
        };
    }

    /** @param  iterable<Status>  $statuses */
    public static function worst(iterable $statuses): self
    {
        $worst = self::Ok;

        foreach ($statuses as $status) {
            if ($status->severity() > $worst->severity()) {
                $worst = $status;
            }
        }

        return $worst;
    }
}
