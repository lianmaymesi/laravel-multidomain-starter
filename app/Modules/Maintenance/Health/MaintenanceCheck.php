<?php

namespace App\Modules\Maintenance\Health;

use App\Modules\Maintenance\Models\PortalSetting;
use App\Support\Health\Check;
use App\Support\Health\Result;

/**
 * Contributed to the health report by the Maintenance module: a portal left
 * in maintenance mode is easy to forget, so it shows as a warning.
 */
class MaintenanceCheck implements Check
{
    public function name(): string
    {
        return 'maintenance';
    }

    public function label(): string
    {
        return 'Maintenance mode';
    }

    public function run(): Result
    {
        if (config('maintenance.global')) {
            return Result::warning('Every portal is in maintenance (APP_MAINTENANCE=true).', ['global' => true]);
        }

        $down = PortalSetting::where('maintenance_mode', true)->orderBy('portal')->pluck('portal')->all();

        return $down === []
            ? Result::ok('All portals are live.', ['down' => []])
            : Result::warning('In maintenance: '.implode(', ', $down).'.', ['down' => $down]);
    }
}
