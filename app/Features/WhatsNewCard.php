<?php

namespace App\Features;

use App\Support\Features\Portal;
use App\Support\Features\PortalFlag;

/**
 * Example flag: a "What's new" card on the app dashboard, shipped dark — off
 * until it is switched on for the app portal from Backoffice → Feature Flags.
 * Delete it (and the @flag block in the dashboard) once you have your own.
 */
class WhatsNewCard extends PortalFlag
{
    public string $name = 'whats-new-card';

    public function label(): string
    {
        return "What's new card";
    }

    public function description(): string
    {
        return 'Shows a "What\'s new" card on the app dashboard.';
    }

    public function portals(): array
    {
        return ['app'];
    }

    protected function initial(Portal $portal): mixed
    {
        return false;
    }
}
