<?php

namespace Atrium\Core\Providers;

use Atrium\Core\Support\Features\Flags;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureFlagsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // One class per flag in app/Features — defining them up front is what
        // lets the backoffice list every flag, not just ones already checked.
        if (is_dir(app_path('Features'))) {
            Feature::discover();
        }

        // @flag(WhatsNewCard::class) … @endflag — scoped to the current portal
        // or user depending on the flag, like @module for modules.
        Blade::if('flag', fn (string $flag) => Flags::active($flag));
    }
}
